<?php

namespace App\Domain\PonyAI\Services\Pipeline;

use App\Domain\PonyAI\Contracts\GeminiClient;
use App\Domain\PonyAI\Exceptions\GeminiException;
use App\Domain\PonyAI\Prompts\CustomerSystemPrompt;
use App\Domain\Product\Models\Product;
use Illuminate\Support\Collection;
use Log;

class AnswerComposer
{
    public function __construct(private readonly GeminiClient $gemini) {}

    /**
     * Ask Gemini to compose a final reply over the candidate products.
     * Returns the reply text plus the set of product/offer IDs the model
     * decided to recommend. The caller is responsible for grounding (dropping
     * any IDs that weren't in $candidates).
     *
     * @param  Collection<int, Product>  $candidates
     * @return array{message: string, product_ids: array<int, string>, offer_ids: array<int, string>}
     */
    public function compose(string $userPrompt, Collection $candidates): array
    {
        $candidateBlock = $this->renderCandidates($candidates);

        $prompt = <<<PROMPT
USER MESSAGE:
"""
{$userPrompt}
"""

CANDIDATE PRODUCTS (the only items you may recommend):
{$candidateBlock}

Respond with the JSON schema described in the system instructions.
PROMPT;

        try {
            $result = $this->gemini->generateJson($prompt, [
                'system_instruction' => CustomerSystemPrompt::text(),
                'temperature' => 0.3,
                'max_output_tokens' => 256,
            ]);
        } catch (GeminiException) {
            return $this->fallback($candidates);
        }

        $payload = $result->decodeJson();
        Log::info('Pony AI Payload', [$payload]);
        $message = is_string($payload['message'] ?? null) ? trim($payload['message']) : '';
        $productIds = $this->extractStringList($payload['product_ids'] ?? null);
        $offerIds = $this->extractStringList($payload['offer_ids'] ?? null);
        if ($message === '') {
            return $this->fallback($candidates);
        }

        return [
            'message' => $message,
            'product_ids' => $productIds,
            'offer_ids' => $offerIds,
        ];
    }

    /**
     * @param  Collection<int, Product>  $candidates
     */
    private function renderCandidates(Collection $candidates): string
    {
        if ($candidates->isEmpty()) {
            return '(no candidate products available)';
        }

        return $candidates
            ->map(function (Product $product): string {
                $title = (string) $product->title;
                $price = $product->base_price !== null
                    ? sprintf('%s %s', (string) $product->base_price, (string) ($product->currency ?? 'EGP'))
                    : 'price unavailable';
                $offerId = $product->relationLoaded('offer') ? (string) ($product->offer?->id ?? '') : '';
                $offerLabel = $product->relationLoaded('offer') ? (string) ($product->offer?->label ?? '') : '';
                $summary = (string) ($product->short_description ?? '');

                return sprintf(
                    '- product_id=%s | offer_id=%s | title=%s | price=%s | offer=%s | summary=%s',
                    $product->id,
                    $offerId,
                    $title,
                    $price,
                    $offerLabel,
                    mb_substr($summary, 0, 160),
                );
            })
            ->implode("\n");
    }

    /**
     * @param  Collection<int, Product>  $candidates
     * @return array{message: string, product_ids: array<int, string>, offer_ids: array<int, string>}
     */
    private function fallback(Collection $candidates): array
    {
        if ($candidates->isEmpty()) {
            return [
                'message' => __('api.pony.no_matches_found') !== 'api.pony.no_matches_found'
                    ? __('api.pony.no_matches_found')
                    : 'I could not find anything that matches your request.',
                'product_ids' => [],
                'offer_ids' => [],
            ];
        }

        return [
            'message' => 'Here are some products that may match what you are looking for.',
            'product_ids' => $candidates->pluck('id')->map(fn ($id) => (string) $id)->all(),
            'offer_ids' => [],
        ];
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
}
