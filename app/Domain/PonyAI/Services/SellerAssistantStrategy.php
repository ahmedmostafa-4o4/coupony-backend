<?php

namespace App\Domain\PonyAI\Services;

use App\Domain\PonyAI\Contracts\GeminiClient;
use App\Domain\PonyAI\DTOs\SellerAssistantReply;
use App\Domain\PonyAI\DTOs\StoreInsightsSnapshot;
use App\Domain\PonyAI\Exceptions\GeminiException;
use App\Domain\PonyAI\Models\PonyConversation;
use App\Domain\PonyAI\Prompts\SellerSystemPrompt;
use App\Domain\PonyAI\Repositories\ConversationRepository;
use App\Domain\PonyAI\Services\Pipeline\SellerInsightsAggregator;
use App\Domain\PonyAI\Services\Pipeline\SellerIntentExtractor;
use App\Domain\PonyAI\Services\Pipeline\SellerSuggestionEngine;
use App\Domain\PonyAI\Support\GroundingValidator;
use App\Domain\PonyAI\Support\PromptSanitizer;
use App\Domain\Product\Models\Product;
use App\Domain\Product\Repositories\ProductRepository;
use App\Domain\Store\Models\Store;
use App\Domain\User\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class SellerAssistantStrategy
{
    public function __construct(
        private readonly ConversationRepository $conversations,
        private readonly SellerIntentExtractor $intent,
        private readonly SellerInsightsAggregator $aggregator,
        private readonly SellerSuggestionEngine $engine,
        private readonly GeminiClient $gemini,
        private readonly ProductRepository $products,
        private readonly PromptSanitizer $sanitizer,
        private readonly GroundingValidator $grounding,
    ) {
    }

    public function handle(
        User $user,
        Store $store,
        string $message,
        ?PonyConversation $conversation = null,
    ): SellerAssistantReply {
        $startedAt = microtime(true);

        $conversation = $conversation
            ?? $this->conversations->startSeller($user, $store, $this->deriveTitle($message));

        $userMessage = $this->conversations->appendUserMessage($conversation, $message);

        $promptForModel = (bool) config('pony.sanitize_prompts', true)
            ? $this->sanitizer->sanitize($message)
            : $message;

        $intent = $this->intent->extract($promptForModel);
        $snapshot = $this->aggregator->snapshot($store);
        $suggestions = $this->engine->suggest($intent, $snapshot);

        $composerPayload = $this->compose($promptForModel, $snapshot, $suggestions);

        $candidates = $this->productsForSnapshot($store, $snapshot);

        [$grounded, $droppedProductIds] = $this->grounding->groundProducts(
            $candidates,
            $composerPayload['product_ids'],
        );
        $offerIds = $this->grounding->groundOffers($candidates, $composerPayload['offer_ids']);

        $assistantMessage = $this->conversations->appendAssistantMessage(
            $conversation,
            $composerPayload['message'],
            [
                'product_ids' => $grounded->pluck('id')->values()->all(),
                'offer_ids' => $offerIds,
                'dropped_product_ids' => $droppedProductIds,
                'suggestions' => $composerPayload['suggestions'],
                'topic' => $intent->topic->value,
                'snapshot' => [
                    'store_id' => $snapshot->storeId,
                    'active_products' => $snapshot->activeProductCount,
                    'total_views' => $snapshot->totalViews,
                    'total_claims' => $snapshot->totalClaims,
                ],
            ],
        );

        $this->logTurn([
            'persona' => 'seller',
            'user_id' => $user->id,
            'store_id' => $store->id,
            'conversation_id' => $conversation->id,
            'topic' => $intent->topic->value,
            'message_length' => mb_strlen($message),
            'sanitized_length' => mb_strlen($promptForModel),
            'suggestion_count' => count($suggestions),
            'returned_count' => $grounded->count(),
            'dropped_count' => count($droppedProductIds),
            'latency_ms' => (int) round((microtime(true) - $startedAt) * 1000),
        ]);

        return new SellerAssistantReply(
            conversation: $conversation->fresh() ?? $conversation,
            userMessage: $userMessage,
            assistantMessage: $assistantMessage,
            message: $composerPayload['message'],
            groundedProducts: $grounded,
            groundedOfferIds: $offerIds,
            droppedProductIds: $droppedProductIds,
            suggestions: $composerPayload['suggestions'],
            snapshot: $snapshot,
        );
    }

    /**
     * @param  array<int, array{topic: string, text: string, product_ids: array<int, string>}>  $suggestions
     * @return array{message: string, product_ids: array<int, string>, offer_ids: array<int, string>, suggestions: array<int, string>}
     */
    private function compose(string $userPrompt, StoreInsightsSnapshot $snapshot, array $suggestions): array
    {
        $snapshotBlock = $snapshot->toPromptBlock();
        $suggestionBlock = $suggestions === []
            ? '(no deterministic suggestions for this snapshot)'
            : implode("\n", array_map(
                static fn(array $row): string => sprintf(
                    '- [%s] %s (product_ids=%s)',
                    (string) $row['topic'],
                    (string) $row['text'],
                    implode(',', $row['product_ids']),
                ),
                $suggestions,
            ));

        $prompt = <<<PROMPT
SELLER MESSAGE:
"""
{$userPrompt}
"""

SNAPSHOT (only data you may reference):
{$snapshotBlock}

DETERMINISTIC SUGGESTIONS (you may rephrase but must not contradict):
{$suggestionBlock}

Respond with the JSON schema described in the system instructions.
PROMPT;

        try {
            $result = $this->gemini->generateJson($prompt, [
                'system_instruction' => SellerSystemPrompt::text(),
                'temperature' => 0.3,
                'max_output_tokens' => 512,
            ]);
        } catch (GeminiException) {
            return $this->fallback($snapshot, $suggestions);
        }

        $payload = $result->decodeJson();

        $message = is_string($payload['message'] ?? null) ? trim($payload['message']) : '';

        if ($message === '') {
            return $this->fallback($snapshot, $suggestions);
        }

        return [
            'message' => $message,
            'product_ids' => $this->extractStringList($payload['product_ids'] ?? null),
            'offer_ids' => $this->extractStringList($payload['offer_ids'] ?? null),
            'suggestions' => $this->extractStringList($payload['suggestions'] ?? null),
        ];
    }

    /**
     * @param  array<int, array{topic: string, text: string, product_ids: array<int, string>}>  $suggestions
     * @return array{message: string, product_ids: array<int, string>, offer_ids: array<int, string>, suggestions: array<int, string>}
     */
    private function fallback(StoreInsightsSnapshot $snapshot, array $suggestions): array
    {
        $suggestionTexts = array_map(static fn(array $row): string => (string) $row['text'], $suggestions);

        if ($suggestionTexts === [] && $snapshot->activeProductCount === 0) {
            return [
                'message' => 'You don\'t have any active products yet - publish some products and I\'ll have data to work with.',
                'product_ids' => [],
                'offer_ids' => [],
                'suggestions' => [],
            ];
        }

        return [
            'message' => $suggestionTexts !== []
                ? 'Here are some suggestions based on your store activity.'
                : 'Your store is healthy - no urgent suggestions right now.',
            'product_ids' => collect($suggestions)->flatMap(fn(array $row) => $row['product_ids'])->unique()->values()->all(),
            'offer_ids' => [],
            'suggestions' => $suggestionTexts,
        ];
    }

    /**
     * @return Collection<int, Product>
     */
    private function productsForSnapshot(Store $store, StoreInsightsSnapshot $snapshot): Collection
    {
        if ($snapshot->productIds === []) {
            return collect();
        }

        return Product::query()
            ->where('store_id', $store->id)
            ->whereIn('id', $snapshot->productIds)
            ->with($this->products->publicRelations())
            ->get();
    }

    /**
     * @return array<int, string>
     */
    private function extractStringList(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $out = [];
        foreach ($value as $item) {
            if (is_string($item) && trim($item) !== '') {
                $out[] = trim($item);
            }
        }

        return array_values(array_unique($out));
    }

    private function deriveTitle(string $message): string
    {
        $trimmed = trim($message);

        return $trimmed === '' ? 'Seller chat' : mb_substr($trimmed, 0, 60);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function logTurn(array $context): void
    {
        $channel = (string) config('pony.logging.channel', 'pony');
        Log::channel($channel)->info('pony.seller_turn', $context);
    }
}
