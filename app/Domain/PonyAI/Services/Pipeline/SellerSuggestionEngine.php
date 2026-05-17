<?php

namespace App\Domain\PonyAI\Services\Pipeline;

use App\Domain\PonyAI\DTOs\SellerIntent;
use App\Domain\PonyAI\DTOs\StoreInsightsSnapshot;
use App\Domain\PonyAI\Enums\SellerTopic;

class SellerSuggestionEngine
{
    /**
     * Produce a list of deterministic suggestion entries based on the snapshot and
     * the seller's intent. Each entry is a short string plus the product_ids it
     * references, so the strategy layer can both display them and feed them into
     * Gemini's prompt as constrained content.
     *
     * @return array<int, array{topic: string, text: string, product_ids: array<int, string>}>
     */
    public function suggest(SellerIntent $intent, StoreInsightsSnapshot $snapshot): array
    {
        $suggestions = [];

        $topicsToConsider = $intent->topic === SellerTopic::FREE_FORM
            ? SellerTopic::cases()
            : [$intent->topic];

        foreach ($topicsToConsider as $topic) {
            $generated = match ($topic) {
                SellerTopic::UNDERPERFORMING_PRODUCTS => $this->underperformingProducts($snapshot),
                SellerTopic::OFFER_SUGGESTION => $this->offerSuggestions($snapshot),
                SellerTopic::INVENTORY_WARNING => $this->inventoryWarnings($snapshot),
                SellerTopic::CAMPAIGN_IDEA => $this->campaignIdeas($snapshot),
                SellerTopic::FREE_FORM => [],
            };

            foreach ($generated as $suggestion) {
                $suggestions[] = $suggestion;
            }
        }

        return $suggestions;
    }

    /**
     * @return array<int, array{topic: string, text: string, product_ids: array<int, string>}>
     */
    private function underperformingProducts(StoreInsightsSnapshot $snapshot): array
    {
        if ($snapshot->underperformingProducts === []) {
            return [];
        }

        return array_map(static fn(array $row): array => [
            'topic' => SellerTopic::UNDERPERFORMING_PRODUCTS->value,
            'text' => sprintf(
                '"%s" has %d views but 0 claims - consider an offer or promotion.',
                (string) ($row['title'] ?? ''),
                (int) ($row['views_count'] ?? 0),
            ),
            'product_ids' => [(string) ($row['id'] ?? '')],
        ], $snapshot->underperformingProducts);
    }

    /**
     * @return array<int, array{topic: string, text: string, product_ids: array<int, string>}>
     */
    private function offerSuggestions(StoreInsightsSnapshot $snapshot): array
    {
        // The same underperforming list is the strongest deterministic offer signal we have.
        return array_map(static fn(array $row): array => [
            'topic' => SellerTopic::OFFER_SUGGESTION->value,
            'text' => sprintf(
                'Run a limited discount on "%s" - %d people viewed it without claiming.',
                (string) ($row['title'] ?? ''),
                (int) ($row['views_count'] ?? 0),
            ),
            'product_ids' => [(string) ($row['id'] ?? '')],
        ], $snapshot->underperformingProducts);
    }

    /**
     * @return array<int, array{topic: string, text: string, product_ids: array<int, string>}>
     */
    private function inventoryWarnings(StoreInsightsSnapshot $snapshot): array
    {
        if ($snapshot->inventoryWarnings === []) {
            return [];
        }

        return array_map(static fn(array $row): array => [
            'topic' => SellerTopic::INVENTORY_WARNING->value,
            'text' => sprintf(
                '"%s" has %d variant(s) at or below the low-stock threshold - restock soon.',
                (string) ($row['title'] ?? ''),
                (int) ($row['low_stock_variants'] ?? 0),
            ),
            'product_ids' => [(string) ($row['id'] ?? '')],
        ], $snapshot->inventoryWarnings);
    }

    /**
     * @return array<int, array{topic: string, text: string, product_ids: array<int, string>}>
     */
    private function campaignIdeas(StoreInsightsSnapshot $snapshot): array
    {
        if ($snapshot->topProducts === []) {
            return [];
        }

        $topIds = array_map(
            static fn(array $row): string => (string) ($row['id'] ?? ''),
            $snapshot->topProducts,
        );

        $titles = implode(', ', array_map(
            static fn(array $row): string => sprintf('"%s"', (string) ($row['title'] ?? '')),
            $snapshot->topProducts,
        ));

        return [[
            'topic' => SellerTopic::CAMPAIGN_IDEA->value,
            'text' => sprintf(
                'Bundle your top-viewed products (%s) into a themed campaign.',
                $titles,
            ),
            'product_ids' => array_values(array_filter($topIds)),
        ]];
    }
}
