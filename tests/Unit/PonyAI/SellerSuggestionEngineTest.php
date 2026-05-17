<?php

namespace Tests\Unit\PonyAI;

use App\Domain\PonyAI\DTOs\SellerIntent;
use App\Domain\PonyAI\DTOs\StoreInsightsSnapshot;
use App\Domain\PonyAI\Enums\SellerTopic;
use App\Domain\PonyAI\Services\Pipeline\SellerSuggestionEngine;
use Tests\TestCase;

class SellerSuggestionEngineTest extends TestCase
{
    private function snapshot(array $overrides = []): StoreInsightsSnapshot
    {
        return new StoreInsightsSnapshot(
            storeId: $overrides['storeId'] ?? 'store-1',
            activeProductCount: $overrides['activeProductCount'] ?? 1,
            pendingProductCount: 0,
            totalViews: 0,
            totalLikes: 0,
            totalFavorites: 0,
            totalClaims: 0,
            totalRedemptions: 0,
            topProducts: $overrides['topProducts'] ?? [],
            underperformingProducts: $overrides['underperformingProducts'] ?? [],
            inventoryWarnings: $overrides['inventoryWarnings'] ?? [],
            productIds: $overrides['productIds'] ?? [],
        );
    }

    public function test_underperforming_topic_yields_offer_suggestions_referencing_the_right_ids(): void
    {
        $snapshot = $this->snapshot([
            'underperformingProducts' => [
                ['id' => 'p-1', 'title' => 'leather wallet', 'views_count' => 40, 'claims_count' => 0],
            ],
        ]);

        $engine = $this->app->make(SellerSuggestionEngine::class);
        $suggestions = $engine->suggest(new SellerIntent(freeText: 'hi', topic: SellerTopic::UNDERPERFORMING_PRODUCTS), $snapshot);

        $this->assertCount(1, $suggestions);
        $this->assertSame(SellerTopic::UNDERPERFORMING_PRODUCTS->value, $suggestions[0]['topic']);
        $this->assertStringContainsString('leather wallet', $suggestions[0]['text']);
        $this->assertStringContainsString('40', $suggestions[0]['text']);
        $this->assertSame(['p-1'], $suggestions[0]['product_ids']);
    }

    public function test_offer_suggestion_topic_uses_underperforming_signal(): void
    {
        $snapshot = $this->snapshot([
            'underperformingProducts' => [
                ['id' => 'p-2', 'title' => 'silver chain', 'views_count' => 25, 'claims_count' => 0],
            ],
        ]);

        $suggestions = $this->app->make(SellerSuggestionEngine::class)
            ->suggest(new SellerIntent(freeText: 'hi', topic: SellerTopic::OFFER_SUGGESTION), $snapshot);

        $this->assertCount(1, $suggestions);
        $this->assertSame(SellerTopic::OFFER_SUGGESTION->value, $suggestions[0]['topic']);
        $this->assertStringContainsString('silver chain', $suggestions[0]['text']);
    }

    public function test_inventory_topic_yields_warnings(): void
    {
        $snapshot = $this->snapshot([
            'inventoryWarnings' => [
                ['id' => 'p-3', 'title' => 'sneakers', 'low_stock_variants' => 2],
            ],
        ]);

        $suggestions = $this->app->make(SellerSuggestionEngine::class)
            ->suggest(new SellerIntent(freeText: 'hi', topic: SellerTopic::INVENTORY_WARNING), $snapshot);

        $this->assertCount(1, $suggestions);
        $this->assertSame(SellerTopic::INVENTORY_WARNING->value, $suggestions[0]['topic']);
        $this->assertStringContainsString('sneakers', $suggestions[0]['text']);
    }

    public function test_campaign_idea_bundles_top_products(): void
    {
        $snapshot = $this->snapshot([
            'topProducts' => [
                ['id' => 'p-4', 'title' => 'mug', 'views_count' => 50, 'claims_count' => 5],
                ['id' => 'p-5', 'title' => 'kettle', 'views_count' => 40, 'claims_count' => 3],
            ],
        ]);

        $suggestions = $this->app->make(SellerSuggestionEngine::class)
            ->suggest(new SellerIntent(freeText: 'hi', topic: SellerTopic::CAMPAIGN_IDEA), $snapshot);

        $this->assertCount(1, $suggestions);
        $this->assertSame(SellerTopic::CAMPAIGN_IDEA->value, $suggestions[0]['topic']);
        $this->assertSame(['p-4', 'p-5'], $suggestions[0]['product_ids']);
        $this->assertStringContainsString('mug', $suggestions[0]['text']);
        $this->assertStringContainsString('kettle', $suggestions[0]['text']);
    }

    public function test_free_form_topic_returns_suggestions_from_every_signal(): void
    {
        $snapshot = $this->snapshot([
            'underperformingProducts' => [
                ['id' => 'p-1', 'title' => 'A', 'views_count' => 15, 'claims_count' => 0],
            ],
            'inventoryWarnings' => [
                ['id' => 'p-2', 'title' => 'B', 'low_stock_variants' => 1],
            ],
            'topProducts' => [
                ['id' => 'p-3', 'title' => 'C', 'views_count' => 30, 'claims_count' => 4],
            ],
        ]);

        $suggestions = $this->app->make(SellerSuggestionEngine::class)
            ->suggest(new SellerIntent(freeText: 'hi'), $snapshot);

        $topics = array_unique(array_column($suggestions, 'topic'));

        $this->assertContains(SellerTopic::UNDERPERFORMING_PRODUCTS->value, $topics);
        $this->assertContains(SellerTopic::OFFER_SUGGESTION->value, $topics);
        $this->assertContains(SellerTopic::INVENTORY_WARNING->value, $topics);
        $this->assertContains(SellerTopic::CAMPAIGN_IDEA->value, $topics);
    }

    public function test_empty_snapshot_yields_no_suggestions(): void
    {
        $suggestions = $this->app->make(SellerSuggestionEngine::class)
            ->suggest(new SellerIntent(freeText: 'hi'), $this->snapshot());

        $this->assertSame([], $suggestions);
    }
}
