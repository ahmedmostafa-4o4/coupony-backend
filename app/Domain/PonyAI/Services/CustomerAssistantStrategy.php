<?php

namespace App\Domain\PonyAI\Services;

use App\Domain\PonyAI\DTOs\AssistantReply;
use App\Domain\PonyAI\Models\PonyConversation;
use App\Domain\PonyAI\Models\PonyMessage;
use App\Domain\PonyAI\Repositories\ConversationRepository;
use App\Domain\PonyAI\Services\Pipeline\AnswerComposer;
use App\Domain\PonyAI\Services\Pipeline\CandidateRetriever;
use App\Domain\PonyAI\Services\Pipeline\EmbeddingReranker;
use App\Domain\PonyAI\Services\Pipeline\IntentExtractor;
use App\Domain\PonyAI\Support\GroundingValidator;
use App\Domain\PonyAI\Support\PromptSanitizer;
use App\Domain\Product\Repositories\ProductRepository;
use App\Domain\User\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class CustomerAssistantStrategy
{
    /**
     * Normalized substrings that indicate a generic catalog-browsing prompt.
     *
     * These are matched against the prompt AFTER a light normalization pass
     * (lowercased, Arabic diacritics stripped, alef variants folded, whitespace
     * collapsed) so common variations such as "أعرض" vs "اعرض" or surrounding
     * punctuation still match.
     */
    private const GENERIC_CATALOG_PHRASES = [
        // Egyptian / MSA Arabic
        'هل يوجد منتجات',
        'هل يوجد منتج',
        'فيه منتجات',
        'فيه منتج',
        'اعرض المنتجات',
        'اعرض منتجات',
        'وريني المنتجات',
        'وريني منتجات',
        'ايه المتاح',
        'ايه المنتجات',
        'المنتجات المتاحة',
        'منتجات متاحة',
        'عندكم ايه',
        'عندكم منتجات',
        'عندك ايه',
        'شو المتاح',
        'شو عندكم',
        // English
        'show products',
        'show me products',
        'show me the products',
        'show me what you have',
        'available products',
        'what do you have',
        'what products',
        'list products',
    ];

    public function __construct(
        private readonly ConversationRepository $conversations,
        private readonly IntentExtractor $intent,
        private readonly CandidateRetriever $retriever,
        private readonly EmbeddingReranker $reranker,
        private readonly AnswerComposer $composer,
        private readonly ProductRepository $products,
        private readonly PromptSanitizer $sanitizer,
        private readonly GroundingValidator $grounding,
    ) {}

    public function handle(User $user, string $message, ?PonyConversation $conversation = null): AssistantReply
    {
        $startedAt = microtime(true);
        $stages = [];

        $stageStart = microtime(true);
        $conversation = $conversation ?? $this->conversations->startCustomer($user, $this->deriveTitle($message));
        $userMessage = $this->conversations->appendUserMessage($conversation, $message);
        $stages['save_user_message'] = $this->elapsedMs($stageStart);

        $promptForModel = (bool) config('pony.sanitize_prompts', true)
            ? $this->sanitizer->sanitize($message)
            : $message;

        // Fast path #1 - generic catalog browsing. Detected locally, never touches Gemini.
        if ($this->isGenericCatalogRequest($promptForModel)) {
            return $this->handleGenericCatalogFastPath(
                user: $user,
                conversation: $conversation,
                userMessage: $userMessage,
                originalMessage: $message,
                promptForModel: $promptForModel,
                startedAt: $startedAt,
                stages: $stages,
            );
        }

        $stageStart = microtime(true);
        $intent = $this->intent->extract($promptForModel);
        $stages['intent_extraction'] = $this->elapsedMs($stageStart);

        $candidateLimit = (int) config('pony.retrieval.candidate_limit', 50);
        $topK = (int) config('pony.retrieval.rerank_top_k', 8);

        $stageStart = microtime(true);
        $candidateIds = $this->retriever->candidates($intent, $candidateLimit);
        $stages['sql_candidate_retrieval'] = $this->elapsedMs($stageStart);

        $fellBackToPopular = false;
        $stages['embedding_rerank'] = 0;

        if ($candidateIds === []) {
            // Pure popularity fallback - guarantees the user never sees an empty
            // result when there are active/approved products in the catalog,
            // even if the intent extractor returned restrictive filters.
            $stageStart = microtime(true);
            $fallback = $this->products->popularPublicProducts($topK, $user);
            $candidates = $fallback;
            $rankedIds = $fallback->pluck('id')->map(static fn ($id): string => (string) $id)->all();
            $fellBackToPopular = $fallback->isNotEmpty();
            $stages['db_hydration'] = $this->elapsedMs($stageStart);
        } else {
            $stageStart = microtime(true);
            $queryText = $intent->combinedQueryText() !== '' ? $intent->combinedQueryText() : $promptForModel;
            $rankedIds = $this->reranker->rerank($queryText, $candidateIds, $topK);
            $stages['embedding_rerank'] = $this->elapsedMs($stageStart);

            $stageStart = microtime(true);
            $candidates = $rankedIds === []
                ? collect()
                : $this->products->publicProductsByIdsInOrder($rankedIds, $user);
            $stages['db_hydration'] = $this->elapsedMs($stageStart);
        }

        // Fast path #2 - fast_mode skips the AnswerComposer Gemini call when we
        // already have at least one candidate. The reply is built deterministically
        // from the products we already loaded.
        $fastMode = (bool) config('pony.fast_mode', true);
        $skippedComposer = false;

        $stageStart = microtime(true);
        if ($fastMode && $candidates->isNotEmpty()) {
            $composed = $this->buildDeterministicComposerOutput($promptForModel, $candidates);
            $skippedComposer = true;
        } else {
            $composed = $this->composer->compose($promptForModel, $candidates);
        }
        $stages['answer_composition'] = $this->elapsedMs($stageStart);

        $stageStart = microtime(true);
        [$grounded, $droppedProductIds] = $this->grounding->groundProducts($candidates, $composed['product_ids']);
        $offerIds = $this->grounding->groundOffers($candidates, $composed['offer_ids']);
        $stages['grounding'] = $this->elapsedMs($stageStart);

        $stageStart = microtime(true);
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
                'fast_path' => $skippedComposer ? 'fast_mode_deterministic' : null,
                'skipped_gemini' => false,
            ],
        );
        $stages['save_assistant_message'] = $this->elapsedMs($stageStart);

        $stages['total'] = $this->elapsedMs($startedAt);

        $this->logTurn([
            'persona' => 'customer',
            'path' => $skippedComposer ? 'fast_mode_deterministic' : 'gemini_composer',
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
            'stages_ms' => $stages,
            'latency_ms' => $stages['total'],
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

    /**
     * @param  array<string, float|int>  $stages
     */
    private function handleGenericCatalogFastPath(
        User $user,
        PonyConversation $conversation,
        PonyMessage $userMessage,
        string $originalMessage,
        string $promptForModel,
        float $startedAt,
        array $stages,
    ): AssistantReply {
        $topK = (int) config('pony.retrieval.rerank_top_k', 8);

        $stages['intent_extraction'] = 0;
        $stages['embedding_rerank'] = 0;
        $stages['answer_composition'] = 0;

        $stageStart = microtime(true);
        $candidates = $this->products->popularPublicProducts($topK, $user);
        // popularPublicProducts already runs the active/approved scope, so its
        // results are safe to surface without further filtering.
        $stages['sql_candidate_retrieval'] = $this->elapsedMs($stageStart);
        $stages['db_hydration'] = 0;

        $stageStart = microtime(true);
        $replyText = $this->genericCatalogReply($originalMessage);
        $productIds = $candidates->pluck('id')->map(static fn ($id): string => (string) $id)->all();
        $stages['grounding'] = $this->elapsedMs($stageStart);

        $stageStart = microtime(true);
        $assistantMessage = $this->conversations->appendAssistantMessage(
            $conversation,
            $replyText,
            [
                'product_ids' => $productIds,
                'offer_ids' => [],
                'dropped_product_ids' => [],
                'candidate_count' => $candidates->count(),
                'reranked_count' => $candidates->count(),
                'fell_back_to_popular' => false,
                'fast_path' => 'generic_catalog',
                'skipped_gemini' => true,
            ],
        );
        $stages['save_assistant_message'] = $this->elapsedMs($stageStart);

        $stages['total'] = $this->elapsedMs($startedAt);

        $this->logTurn([
            'persona' => 'customer',
            'path' => 'generic_catalog',
            'skipped_gemini' => true,
            'user_id' => $user->id,
            'conversation_id' => $conversation->id,
            'message_length' => mb_strlen($originalMessage),
            'sanitized_length' => mb_strlen($promptForModel),
            'returned_count' => $candidates->count(),
            'stages_ms' => $stages,
            'latency_ms' => $stages['total'],
        ]);

        return new AssistantReply(
            conversation: $conversation->fresh() ?? $conversation,
            userMessage: $userMessage,
            assistantMessage: $assistantMessage,
            message: $replyText,
            groundedProducts: $candidates,
            groundedOfferIds: [],
            droppedProductIds: [],
        );
    }

    /**
     * Decide whether the user's prompt is a generic catalog browsing request
     * that we can answer without any Gemini calls.
     */
    private function isGenericCatalogRequest(string $prompt): bool
    {
        $normalized = $this->normalizeForGenericMatch($prompt);

        if ($normalized === '') {
            return false;
        }

        foreach (self::GENERIC_CATALOG_PHRASES as $phrase) {
            if (mb_strpos($normalized, $phrase) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Lightweight normalization tuned for matching short Arabic / English
     * phrases - removes Arabic diacritics, folds alef variants, lowercases,
     * collapses whitespace. Does NOT lemmatize.
     */
    private function normalizeForGenericMatch(string $input): string
    {
        $value = mb_strtolower($input);

        // Strip Arabic diacritics (harakat U+064B..U+0652) and superscript alef U+0670.
        $value = preg_replace('/[\x{064B}-\x{0652}\x{0670}]/u', '', $value) ?? $value;
        // Strip Tatweel (kashida).
        $value = str_replace("\u{0640}", '', $value);
        // Fold alef variants to the bare alef so "أعرض" / "إعرض" / "آعرض" all match "اعرض".
        $value = strtr($value, [
            "\u{0623}" => "\u{0627}", // hamza-on-alef
            "\u{0625}" => "\u{0627}", // hamza-under-alef
            "\u{0622}" => "\u{0627}", // alef madda
        ]);
        // Collapse internal whitespace, then strip leading/trailing punctuation.
        // The trim is done via a unicode-aware regex because trim() with a mask
        // is byte-based, and multi-byte Arabic punctuation (U+060C, U+061F) shares
        // its leading byte with the alef character - using trim() with those in
        // the mask would shred the first letter of an Arabic prompt.
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;
        $value = preg_replace('/^[\s?.,!\x{061F}\x{060C}]+|[\s?.,!\x{061F}\x{060C}]+$/u', '', $value) ?? $value;

        return $value;
    }

    private function genericCatalogReply(string $originalMessage): string
    {
        // If the user wrote anything in Arabic, answer in Arabic. Otherwise English.
        if (preg_match('/\p{Arabic}/u', $originalMessage) === 1) {
            return 'أكيد، دي بعض المنتجات المتاحة حاليًا:';
        }

        return 'Sure, here are some of the products available right now:';
    }

    /**
     * Build the {message, product_ids, offer_ids} shape that AnswerComposer
     * would normally return, but without calling Gemini. Used when fast_mode
     * is on and we already have a non-empty candidate set.
     *
     * @param  Collection<int, \App\Domain\Product\Models\Product>  $candidates
     * @return array{message: string, product_ids: array<int, string>, offer_ids: array<int, string>}
     */
    private function buildDeterministicComposerOutput(string $promptForModel, Collection $candidates): array
    {
        $message = preg_match('/\p{Arabic}/u', $promptForModel) === 1
            ? 'اخترت لك المنتجات دي بناءً على طلبك:'
            : 'Here are some products that match your request:';

        return [
            'message' => $message,
            'product_ids' => $candidates->pluck('id')->map(static fn ($id): string => (string) $id)->all(),
            'offer_ids' => [],
        ];
    }

    private function deriveTitle(string $message): string
    {
        $trimmed = trim($message);

        if ($trimmed === '') {
            return 'New chat';
        }

        return mb_substr($trimmed, 0, 60);
    }

    private function elapsedMs(float $startedAt): int
    {
        return (int) round((microtime(true) - $startedAt) * 1000);
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
