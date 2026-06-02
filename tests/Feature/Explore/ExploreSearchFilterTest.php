<?php

namespace Tests\Feature\Explore;

use App\Domain\Explore\Services\ExploreService;
use App\Domain\Product\Enums\ProductOfferStatus;
use App\Domain\Product\Enums\ProductOfferType;
use App\Domain\Product\Models\Product;
use App\Domain\Product\Models\ProductOffer;
use App\Domain\Store\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests for search filter with Arabic text support.
 *
 * Validates: Requirements 9.1, 9.2, 9.3, 9.4
 *
 * Note: getTrendingOffers, getFlashOffers, getNearbyOffers, and getPickedOffers
 * use MySQL-specific SQL (INTERVAL, GREATEST, DATEDIFF, Haversine) that cannot
 * be tested with SQLite. The search filter logic in those methods uses the same
 * LIKE pattern as getTopStores, which is verified here.
 */
class ExploreSearchFilterTest extends TestCase
{
    use RefreshDatabase;

    private Store $store;
    private Product $product;
    private ExploreService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(ExploreService::class);

        // Create an active store with Arabic name
        $this->store = Store::factory()->active()->create([
            'name' => 'متجر الكوبونات العربي',
            'rating_avg' => 4.5,
        ]);

        // Create an active, approved product with Arabic title
        $this->product = Product::factory()->active()->approved()->create([
            'store_id' => $this->store->id,
            'title' => 'عرض خاص على الهواتف',
            'base_price' => 100.00,
            'favorites_count' => 5,
        ]);

        // Create an active offer with Arabic label
        ProductOffer::where('product_id', $this->product->id)->delete();
        ProductOffer::factory()->create([
            'product_id' => $this->product->id,
            'type' => ProductOfferType::PERCENTAGE,
            'status' => ProductOfferStatus::ACTIVE,
            'label' => 'خصم كبير على الإلكترونيات',
            'percentage_value' => 20,
        ]);
    }

    /**
     * Test that top_stores search matches Arabic store name.
     * Validates: Requirements 9.1, 9.2, 9.3
     */
    public function test_top_stores_search_matches_arabic_store_name(): void
    {
        $results = $this->service->getTopStores(['search' => 'الكوبونات']);

        $this->assertNotEmpty($results, 'Top stores should match Arabic store name');
        $this->assertEquals('متجر الكوبونات العربي', $results->first()['name']);
    }

    /**
     * Test that top_stores search matches Arabic product offer label.
     * Validates: Requirements 9.1, 9.4
     */
    public function test_top_stores_search_matches_arabic_offer_label(): void
    {
        $results = $this->service->getTopStores(['search' => 'الإلكترونيات']);

        $this->assertNotEmpty($results, 'Top stores should match Arabic offer label');
        $this->assertEquals('متجر الكوبونات العربي', $results->first()['name']);
    }

    /**
     * Test that top_stores search matches Arabic product title.
     * Validates: Requirements 9.1, 9.4
     */
    public function test_top_stores_search_matches_arabic_product_title(): void
    {
        $results = $this->service->getTopStores(['search' => 'الهواتف']);

        $this->assertNotEmpty($results, 'Top stores should match Arabic product title');
        $this->assertEquals('متجر الكوبونات العربي', $results->first()['name']);
    }

    /**
     * Test that top_stores search is case-insensitive for English text.
     * Validates: Requirements 9.2
     */
    public function test_top_stores_search_is_case_insensitive(): void
    {
        $store = Store::factory()->active()->create([
            'name' => 'Electronics Store',
            'rating_avg' => 4.0,
        ]);

        $product = Product::factory()->active()->approved()->create([
            'store_id' => $store->id,
            'title' => 'Phone Deal',
            'base_price' => 200.00,
            'favorites_count' => 3,
        ]);

        ProductOffer::where('product_id', $product->id)->delete();
        ProductOffer::factory()->create([
            'product_id' => $product->id,
            'type' => ProductOfferType::PERCENTAGE,
            'status' => ProductOfferStatus::ACTIVE,
            'label' => 'Big Discount',
            'percentage_value' => 15,
        ]);

        // Search with different case - should still match
        $results = $this->service->getTopStores(['search' => 'ELECTRONICS']);

        $this->assertNotEmpty($results, 'Search should be case-insensitive');
        $this->assertEquals('Electronics Store', $results->first()['name']);
    }

    /**
     * Test that non-matching search returns empty results.
     * Validates: Requirements 9.1
     */
    public function test_non_matching_search_returns_empty(): void
    {
        $results = $this->service->getTopStores(['search' => 'كلمة_غير_موجودة_أبداً']);

        $this->assertEmpty($results);
    }

    /**
     * Test that search filter excludes non-matching stores.
     * Validates: Requirements 9.1, 9.4
     */
    public function test_search_excludes_non_matching_stores(): void
    {
        // Create a second store that should NOT match Arabic search
        $otherStore = Store::factory()->active()->create([
            'name' => 'Other Store',
            'rating_avg' => 5.0,
        ]);

        $otherProduct = Product::factory()->active()->approved()->create([
            'store_id' => $otherStore->id,
            'title' => 'Other Product',
            'base_price' => 50.00,
            'favorites_count' => 10,
        ]);

        ProductOffer::where('product_id', $otherProduct->id)->delete();
        ProductOffer::factory()->create([
            'product_id' => $otherProduct->id,
            'type' => ProductOfferType::PERCENTAGE,
            'status' => ProductOfferStatus::ACTIVE,
            'label' => 'Other Offer',
            'percentage_value' => 30,
        ]);

        // Search for Arabic term - should only return the Arabic store
        $results = $this->service->getTopStores(['search' => 'الكوبونات']);

        $this->assertCount(1, $results);
        $this->assertEquals('متجر الكوبونات العربي', $results->first()['name']);
    }

    /**
     * Test that search matches partial Arabic words.
     * Validates: Requirements 9.1, 9.3
     */
    public function test_search_matches_partial_arabic_text(): void
    {
        // Search for a partial word from the store name
        $results = $this->service->getTopStores(['search' => 'كوبون']);

        $this->assertNotEmpty($results, 'Search should match partial Arabic text via LIKE');
    }
}
