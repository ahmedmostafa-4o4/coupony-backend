<?php

namespace App\Domain\PonyAI\Services;

use App\Domain\PonyAI\DTOs\AssistantReply;
use App\Domain\PonyAI\Models\PonyConversation;
use App\Domain\PonyAI\Repositories\ConversationRepository;
use App\Domain\PonyAI\Services\Pipeline\AnswerComposer;
use App\Domain\PonyAI\Services\Pipeline\CandidateRetriever;
use App\Domain\PonyAI\Services\Pipeline\EmbeddingReranker;
use App\Domain\PonyAI\Services\Pipeline\IntentExtractor;
use App\Domain\PonyAI\Support\GroundingValidator;
use App\Domain\PonyAI\Support\PromptSanitizer;
use App\Domain\Product\Repositories\ProductRepository;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\Log;

class CustomerAssistantStrategy
{
    public function __construct(
        private readonly ConversationRepository $conversations,
        private readonly IntentExtractor $intent,
        private readonly CandidateRetriever $retriever,
        private readonly EmbeddingReranker $reranker,
        private readonly AnswerComposer $composer,
        private readonly ProductRepository $products,
        private readonly PromptSanitizer $sanitizer,
        private readonly GroundingValidator $grounding,
    ) {
    }

    public function handle(User $user, string $message, ?PonyConversation $conversation = null): AssistantReply
    {
        $startedAt = microtime(true);
        $conversation = $conversation ?? $this->conversations->startCustomer($user, $this->deriveTitle($message));

        $userMessage = $this->conversations->appendUserMessage($conversation, $message);

        $promptForModel = (bool) config('pony.sanitize_prompts', true)
            ? $this->sanitizer->sanitize($message)
            : $message;

        $intent = $this->intent->extract($promptForModel);

        $candidateLimit = (int) config('pony.retrieval.candidate_limit', 50);
        $topK = (int) config('pony.retrieval.rerank_top_k', 8);

        $candidateIds = $this->retriever->candidates($intent, $candidateLimit);

        $fellBackToPopular = false;

        if ($candidateIds === []) {
            // Pure popularity fallback - guarantees the user never sees an empty
            // result when there are active/approved products in the catalog,
            // even if the intent extractor returned restrictive filters.
            $fallback = $this->products->popularPublicProducts($topK, $user);
            $candidates = $fallback;
            $rankedIds = $fallback->pluck('id')->map(static fn($id): string => (string) $id)->all();
            $fellBackToPopular = $fallback->isNotEmpty();
        } else {
            $queryText = $intent->combinedQueryText() !== '' ? $intent->combinedQueryText() : $promptForModel;
            $rankedIds = $this->reranker->rerank($queryText, $candidateIds, $topK);

            $candidates = $rankedIds === []
                ? collect()
                : $this->products->publicProductsByIdsInOrder($rankedIds, $user);
        }

        $composed = $this->composer->compose($promptForModel, $candidates);

        [$grounded, $droppedProductIds] = $this->grounding->groundProducts($candidates, $composed['product_ids']);
        $offerIds = $this->grounding->groundOffers($candidates, $composed['offer_ids']);

        $assistantMessage = $this->conversations->appendAssistantMessage(
            $conversation,
            $composed['message'],
            [
                'product_ids' => $grounded->pluck('id')->values()->all(),
                'offer_ids' => $offerIds,
                'dropped_product_ids' => $droppedProductIds,
                'intent' => [
                    'category_id' => $intent->categoryId,
                    'price_min' => $intent->priceMin,
                    'price_max' => $intent->priceMax,
                    'attributes' => $intent->attributes,
                    'semantic_query' => $intent->semanticQuery,
                    'arabic_query' => $intent->arabicQuery,
                    'keywords' => $intent->keywords,
                    'is_generic_catalog_request' => $intent->isGenericCatalogRequest,
                ],
                'candidate_count' => count($candidateIds),
                'reranked_count' => count($rankedIds),
                'fell_back_to_popular' => $fellBackToPopular,
            ],
        );

        $this->logTurn([
            'persona' => 'customer',
            'user_id' => $user->id,
            'conversation_id' => $conversation->id,
            'message_length' => mb_strlen($message),
            'sanitized_length' => mb_strlen($promptForModel),
            'is_generic_catalog_request' => $intent->isGenericCatalogRequest,
            'fell_back_to_popular' => $fellBackToPopular,
            'candidate_count' => count($candidateIds),
            'reranked_count' => count($rankedIds),
            'returned_count' => $grounded->count(),
            'dropped_count' => count($droppedProductIds),
            'latency_ms' => (int) round((microtime(true) - $startedAt) * 1000),
        ]);

        return new AssistantReply(
            conversation: $conversation->fresh() ?? $conversation,
            userMessage: $userMessage,
            assistantMessage: $assistantMessage,
            message: $composed['message'],
            groundedProducts: $grounded,
            groundedOfferIds: $offerIds,
            droppedProductIds: $droppedProductIds,
        );
    }

    private function deriveTitle(string $message): string
    {
        $trimmed = trim($message);

        if ($trimmed === '') {
            return 'New chat';
        }

        return mb_substr($trimmed, 0, 60);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function logTurn(array $context): void
    {
        $channel = (string) config('pony.logging.channel', 'pony');
        Log::channel($channel)->info('pony.customer_turn', $context);
    }
}
