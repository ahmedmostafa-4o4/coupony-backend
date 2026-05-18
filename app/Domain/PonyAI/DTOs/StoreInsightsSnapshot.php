<?php

namespace App\Domain\PonyAI\DTOs;

final class StoreInsightsSnapshot
{
    /**
     * @param  array<int, array<string, mixed>>  $topProducts
     * @param  array<int, array<string, mixed>>  $underperformingProducts
     * @param  array<int, array<string, mixed>>  $inventoryWarnings
     * @param  array<int, string>  $productIds
     */
    public function __construct(
        public readonly string $storeId,
        public readonly int $activeProductCount,
        public readonly int $pendingProductCount,
        public readonly int $totalViews,
        public readonly int $totalLikes,
        public readonly int $totalFavorites,
        public readonly int $totalClaims,
        public readonly int $totalRedemptions,
        public readonly array $topProducts,
        public readonly array $underperformingProducts,
        public readonly array $inventoryWarnings,
        public readonly array $productIds,
    ) {}

    /**
     * Compact representation for embedding into Gemini prompts. No raw FK columns,
     * just human-friendly facts the model is allowed to quote.
     */
    public function toPromptBlock(): string
    {
        $lines = [];
        $lines[] = sprintf('Store snapshot (store_id=%s):', $this->storeId);
        $lines[] = sprintf(
            '  active_products=%d, pending_products=%d',
            $this->activeProductCount,
            $this->pendingProductCount,
        );
        $lines[] = sprintf(
            '  views=%d, likes=%d, favorites=%d, claims=%d, redemptions=%d',
            $this->totalViews,
            $this->totalLikes,
            $this->totalFavorites,
            $this->totalClaims,
            $this->totalRedemptions,
        );

        $lines[] = 'Top products by views:';
        if ($this->topProducts === []) {
            $lines[] = '  (none)';
        } else {
            foreach ($this->topProducts as $row) {
                $lines[] = sprintf(
                    '  - product_id=%s | title=%s | views=%d | claims=%d',
                    (string) ($row['id'] ?? ''),
                    (string) ($row['title'] ?? ''),
                    (int) ($row['views_count'] ?? 0),
                    (int) ($row['claims_count'] ?? 0),
                );
            }
        }

        $lines[] = 'Underperforming products (views without claims):';
        if ($this->underperformingProducts === []) {
            $lines[] = '  (none)';
        } else {
            foreach ($this->underperformingProducts as $row) {
                $lines[] = sprintf(
                    '  - product_id=%s | title=%s | views=%d | claims=%d',
                    (string) ($row['id'] ?? ''),
                    (string) ($row['title'] ?? ''),
                    (int) ($row['views_count'] ?? 0),
                    (int) ($row['claims_count'] ?? 0),
                );
            }
        }

        $lines[] = 'Inventory warnings:';
        if ($this->inventoryWarnings === []) {
            $lines[] = '  (none)';
        } else {
            foreach ($this->inventoryWarnings as $row) {
                $lines[] = sprintf(
                    '  - product_id=%s | title=%s | low_stock_variants=%d',
                    (string) ($row['id'] ?? ''),
                    (string) ($row['title'] ?? ''),
                    (int) ($row['low_stock_variants'] ?? 0),
                );
            }
        }

        return implode("\n", $lines);
    }
}
