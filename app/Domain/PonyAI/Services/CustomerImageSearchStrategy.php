<?php

namespace App\Domain\PonyAI\Services;

use App\Domain\PonyAI\DTOs\AssistantReply;
use App\Domain\PonyAI\Enums\PonyMessageRole;
use App\Domain\PonyAI\Models\PonyConversation;
use App\Domain\PonyAI\Repositories\ConversationRepository;
use App\Domain\PonyAI\Services\Pipeline\AnswerComposer;
use App\Domain\PonyAI\Services\Pipeline\CandidateRetriever;
use App\Domain\PonyAI\Services\Pipeline\ImageQueryUnderstander;
use App\Domain\PonyAI\Services\Pipeline\ImageRanker;
use App\Domain\PonyAI\Support\GroundingValidator;
use App\Domain\PonyAI\Support\PromptSanitizer;
use App\Domain\Product\Repositories\ProductRepository;
use App\Domain\User\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CustomerImageSearchStrategy
{
    public function __construct(
        private readonly ConversationRepository $conversations,
        private readonly ImageQueryUnderstander $vision,
        private readonly CandidateRetriever $retriever,
        private readonly ImageRanker $ranker,
        private readonly AnswerComposer $composer,
        private readonly ProductRepository $products,
        private readonly PromptSanitizer $sanitizer,
        private readonly GroundingValidator $grounding,
    ) {
    }

    public function handle(
        User $user,
        UploadedFile $image,
        string $extraMessage = '',
        ?PonyConversation $conversation = null,
    ): AssistantReply {
        $startedAt = microtime(true);

        $conversation = $conversation
            ?? $this->conversations->startCustomer($user, $this->deriveTitle($extraMessage));

        $storedPath = $this->storeImage($user, $image);
        $bytes = (string) file_get_contents($image->getRealPath());
        $mime = (string) ($image->getMimeType() ?: 'application/octet-stream');

        $userMessage = $this->conversations->appendMessage(
            $conversation,
            PonyMessageRole::USER,
            $extraMessage,
            attachments: [
                'image' => $storedPath,
                'mime' => $mime,
            ],
        );

        $sanitizedMessage = (bool) config('pony.sanitize_prompts', true)
            ? $this->sanitizer->sanitize($extraMessage)
            : $extraMessage;

        $understanding = $this->vision->understand($bytes, $mime);
        $intent = $understanding->toIntent($sanitizedMessage);

        $candidateLimit = (int) config('pony.retrieval.candidate_limit', 50);
        $topK = (int) config('pony.retrieval.rerank_top_k', 8);

        $candidateIds = $this->retriever->candidates($intent, $candidateLimit);
        $rankedIds = $this->ranker->rerank(
            $bytes,
            $mime,
            $understanding->caption,
            $candidateIds,
            $topK,
        );

        $candidates = $rankedIds === []
            ? collect()
            : $this->products->publicProductsByIdsInOrder($rankedIds, $user);

        $composerPrompt = $sanitizedMessage !== ''
            ? sprintf('[Image search] User caption: "%s". Vision caption: "%s".', $sanitizedMessage, $understanding->caption)
            : sprintf('[Image search] Vision caption: "%s".', $understanding->caption);

        $composed = $this->composer->compose($composerPrompt, $candidates);

        [$grounded, $droppedProductIds] = $this->grounding->groundProducts($candidates, $composed['product_ids']);
        $offerIds = $this->grounding->groundOffers($candidates, $composed['offer_ids']);

        $assistantMessage = $this->conversations->appendAssistantMessage(
            $conversation,
            $composed['message'],
            [
                'product_ids' => $grounded->pluck('id')->values()->all(),
                'offer_ids' => $offerIds,
                'dropped_product_ids' => $droppedProductIds,
                'vision' => [
                    'caption' => $understanding->caption,
                    'category_guess' => $understanding->categoryGuess,
                    'color' => $understanding->color,
                    'attributes' => $understanding->attributes,
                ],
                'candidate_count' => count($candidateIds),
                'reranked_count' => count($rankedIds),
            ],
        );

        $this->logTurn([
            'persona' => 'customer_image',
            'user_id' => $user->id,
            'conversation_id' => $conversation->id,
            'mime' => $mime,
            'bytes' => strlen($bytes),
            'message_length' => mb_strlen($extraMessage),
            'sanitized_length' => mb_strlen($sanitizedMessage),
            'caption_length' => mb_strlen($understanding->caption),
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

    private function storeImage(User $user, UploadedFile $image): string
    {
        $extension = strtolower($image->extension() ?: 'bin');
        $filename = Str::uuid().'.'.$extension;
        $directory = "pony/queries/{$user->id}";

        return $image->storeAs($directory, $filename, 'local')
            ?: throw new \RuntimeException('Failed to store image upload.');
    }

    private function deriveTitle(string $message): string
    {
        $trimmed = trim($message);

        if ($trimmed === '') {
            return 'Image search';
        }

        return mb_substr($trimmed, 0, 60);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function logTurn(array $context): void
    {
        $channel = (string) config('pony.logging.channel', 'pony');
        Log::channel($channel)->info('pony.customer_image_turn', $context);
    }
}
