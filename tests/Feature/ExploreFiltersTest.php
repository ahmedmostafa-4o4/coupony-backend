<?php

namespace Tests\Feature;

use App\Domain\Product\Enums\ProductApprovalStatus;
use App\Domain\Product\Enums\ProductOfferStatus;
use App\Domain\Product\Enums\ProductOfferType;
use App\Domain\Product\Enums\ProductStatus;
use App\Domain\Product\Models\Category;
use App\Domain\Product\Models\Product;
use App\Domain\Product\Models\ProductOffer;
use App\Domain\Store\Enums\StoreStatus;
use App\Domain\Store\Models\Store;
use App\Domain\Store\Models\StoreCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExploreFiltersTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Helper to create an active product with an active offer in an active store.
     */
    private function createActiveProductWithOffer(array $productAttrs = [], array $storeAttrs = [], array $offerAttrs = []): Product
    {
        $store = Store::factory()->active()->create($storeAttrs);

        $product = Product::factory()->active()->approved()->create(array_merge([
            'store_id' => $store->id,
            'base_price' => 100.00,
            'favorites_count' => 0,
        ], $productAttrs));

        // Remove the auto-created offer and create one with specific attributes
        $product->offer()->delete();
        ProductOffer::factory()->create(array_merge([
            'product_id' => $product->id,
            'type' => ProductOfferType::PERCENTAGE,
            'status' => ProductOfferStatus::ACTIVE,
            'percentage_value' => 20,
            'fixed_amount' => null,
        ], $offerAttrs));

        return $product;
    }

    /**
     * Test: Combined filters use AND logic on the picks endpoint.
     * When interest_id AND activity_id are both provided, only products
     * matching BOTH conditions are returned.
     *
     * Validates: Requirements 15.1, 15.2
     */
    public function test_picks_combined_interest_and_activity_filters_use_and_logic(): void
    {
        $category = Category::factory()->create(['is_active' => true]);
        $storeCategory = StoreCategory::factory()->create(['is_active' => true]);

        // Product A: matches interest_id only (not activity_id)
        $productA = $this->createActiveProductWithOffer(['title' => 'Product A']);
        $productA->categories()->attach($category->id);

        // Product B: matches activity_id only (not interest_id)
        $storeB = Store::factory()->active()->create();
        $storeB->categories()->attach($storeCategory->id);
        $productB = $this->createActiveProductWithOffer(['title' => 'Product B', 'store_id' => $storeB->id]);

        // Product C: matches BOTH interest_id AND activity_id
        $storeC = Store::factory()->active()->create();
        $storeC->categories()->attach($storeCategory->id);
        $productC = $this->createActiveProductWithOffer(['title' => 'Product C', 'store_id' => $storeC->id]);
        $productC->categories()->attach($category->id);

        // Apply both filters - only Product C should be returned
        $response = $this->getJson("/api/v1/explore/picks?interest_id={$category->id}&activity_id={$storeCategory->id}");

        $response->assertOk();
        $data = $response->json('data');

        // Only Product C matches both filters (AND logic)
        $returnedProductIds = collect($data)->pluck('product_id')->toArray();
        $this->assertContains($productC->id, $returnedProductIds);
        $this->assertNotContains($productA->id, $returnedProductIds);
        $this->assertNotContains($productB->id, $returnedProductIds);
    }

    /**
     * Test: Combined interest_id, activity_id, and search filters use AND logic.
     *
     * Validates: Requirements 15.1, 15.2
     */
    public function test_picks_combined_interest_activity_and_search_filters_use_and_logic(): void
    {
        $category = Category::factory()->create(['is_active' => true]);
        $storeCategory = StoreCategory::factory()->create(['is_active' => true]);

        // Product that matches interest + activity but NOT search
        $storeX = Store::factory()->active()->create(['name' => 'Alpha Store']);
        $storeX->categories()->attach($storeCategory->id);
        $productX = $this->createActiveProductWithOffer(['title' => 'Alpha Widget', 'store_id' => $storeX->id]);
        $productX->categories()->attach($category->id);

        // Product that matches interest + activity + search
        $storeY = Store::factory()->active()->create(['name' => 'Beta Store']);
        $storeY->categories()->attach($storeCategory->id);
        $productY = $this->createActiveProductWithOffer(['title' => 'Beta Gadget', 'store_id' => $storeY->id]);
        $productY->categories()->attach($category->id);

        // Search for "Beta" - only productY matches all three filters
        $response = $this->getJson("/api/v1/explore/picks?interest_id={$category->id}&activity_id={$storeCategory->id}&search=Beta");

        $response->assertOk();
        $data = $response->json('data');

        $returnedProductIds = collect($data)->pluck('product_id')->toArray();
        $this->assertContains($productY->id, $returnedProductIds);
        $this->assertNotContains($productX->id, $returnedProductIds);
    }

    /**
     * Test: Combined filters with min_discount_percent use AND logic.
     *
     * Validates: Requirements 15.1, 15.2
     */
    public function test_picks_combined_filters_with_min_discount_percent_use_and_logic(): void
    {
        $category = Category::factory()->create(['is_active' => true]);

        // Product with 10% discount - matches interest but NOT min_discount_percent=30
        $productLow = $this->createActiveProductWithOffer(
            ['title' => 'Low Discount Product', 'base_price' => 100.00],
            [],
            ['percentage_value' => 10, 'type' => ProductOfferType::PERCENTAGE]
        );
        $productLow->categories()->attach($category->id);

        // Product with 50% discount - matches interest AND min_discount_percent=30
        $productHigh = $this->createActiveProductWithOffer(
            ['title' => 'High Discount Product', 'base_price' => 100.00],
            [],
            ['percentage_value' => 50, 'type' => ProductOfferType::PERCENTAGE]
        );
        $productHigh->categories()->attach($category->id);

        $response = $this->getJson("/api/v1/explore/picks?interest_id={$category->id}&min_discount_percent=30");

        $response->assertOk();
        $data = $response->json('data');

        $returnedProductIds = collect($data)->pluck('product_id')->toArray();
        $this->assertContains($productHigh->id, $returnedProductIds);
        $this->assertNotContains($productLow->id, $returnedProductIds);
    }

    /**
     * Test: When combined filters produce zero results, the response has
     * empty data array with valid pagination metadata (total: 0, has_more: false).
     *
     * Validates: Requirement 15.3
     */
    public function test_picks_combined_filters_with_no_results_returns_empty_data_with_valid_pagination(): void
    {
        $category = Category::factory()->create(['is_active' => true]);
        $storeCategory = StoreCategory::factory()->create(['is_active' => true]);

        // Create a product that matches interest_id but NOT activity_id
        $productA = $this->createActiveProductWithOffer(['title' => 'Lonely Product']);
        $productA->categories()->attach($category->id);

        // Apply both filters - no product matches both
        $response = $this->getJson("/api/v1/explore/picks?interest_id={$category->id}&activity_id={$storeCategory->id}&search=NonExistentTerm");

        $response->assertOk();
        $response->assertJsonPath('data', []);
        $response->assertJsonPath('pagination.total', 0);
        $response->assertJsonPath('pagination.total_pages', 0);
        $response->assertJsonPath('pagination.has_more', false);
        $response->assertJsonPath('pagination.page', 1);
        $response->assertJsonPath('pagination.page_size', 12);
    }

    /**
     * Test: sort_by does not filter results, only affects ordering.
     * Combined with other filters, sort_by should not reduce the result set.
     *
     * Validates: Requirements 15.1, 15.2
     */
    public function test_picks_sort_by_does_not_filter_results(): void
    {
        $category = Category::factory()->create(['is_active' => true]);

        $product1 = $this->createActiveProductWithOffer(
            ['title' => 'Sort Test 1', 'base_price' => 100.00],
            [],
            ['percentage_value' => 40, 'type' => ProductOfferType::PERCENTAGE]
        );
        $product1->categories()->attach($category->id);

        $product2 = $this->createActiveProductWithOffer(
            ['title' => 'Sort Test 2', 'base_price' => 100.00],
            [],
            ['percentage_value' => 20, 'type' => ProductOfferType::PERCENTAGE]
        );
        $product2->categories()->attach($category->id);

        // Apply interest_id filter with sort_by - both products should still be returned
        $response = $this->getJson("/api/v1/explore/picks?interest_id={$category->id}&sort_by=highest_discount");

        $response->assertOk();
        $data = $response->json('data');

        $returnedProductIds = collect($data)->pluck('product_id')->toArray();
        $this->assertContains($product1->id, $returnedProductIds);
        $this->assertContains($product2->id, $returnedProductIds);
        $this->assertCount(2, $data);
    }

    /**
     * Test: Bootstrap endpoint combined filters use AND logic across sections.
     * When interest_id and activity_id are both provided, only matching products appear.
     *
     * Validates: Requirements 15.1, 15.2
     */
    public function test_bootstrap_combined_filters_use_and_logic(): void
    {
        $category = Category::factory()->create(['is_active' => true]);
        $storeCategory = StoreCategory::factory()->create(['is_active' => true]);

        // Product that matches BOTH interest_id AND activity_id
        $storeMatching = Store::factory()->active()->create();
        $storeMatching->categories()->attach($storeCategory->id);
        $productMatching = $this->createActiveProductWithOffer(['title' => 'Matching Product', 'store_id' => $storeMatching->id]);
        $productMatching->categories()->attach($category->id);

        // Product that matches only interest_id
        $productInterestOnly = $this->createActiveProductWithOffer(['title' => 'Interest Only']);
        $productInterestOnly->categories()->attach($category->id);

        $response = $this->getJson("/api/v1/explore?interest_id={$category->id}&activity_id={$storeCategory->id}");

        $response->assertOk();

        // Trending section should only contain the matching product
        $trending = $response->json('data.trending');
        $trendingProductIds = collect($trending)->pluck('product_id')->toArray();

        if (!empty($trendingProductIds)) {
            $this->assertContains($productMatching->id, $trendingProductIds);
            $this->assertNotContains($productInterestOnly->id, $trendingProductIds);
        }
    }

    /**
     * Test: Bootstrap endpoint returns empty arrays for sections when combined filters match nothing.
     *
     * Validates: Requirement 15.3
     */
    public function test_bootstrap_combined_filters_with_no_results_returns_empty_sections(): void
    {
        $category = Category::factory()->create(['is_active' => true]);
        $storeCategory = StoreCategory::factory()->create(['is_active' => true]);

        // No products match both filters
        $response = $this->getJson("/api/v1/explore?interest_id={$category->id}&activity_id={$storeCategory->id}&search=ZZZNonExistent");

        $response->assertOk();
        $response->assertJsonPath('data.trending', []);
        $response->assertJsonPath('data.flash', []);
        $response->assertJsonPath('data.top_stores', []);
        $response->assertJsonPath('data.nearby', []);
    }

    /**
     * Test: All five filter parameters can be combined in a single request to picks endpoint.
     *
     * Validates: Requirement 15.2
     */
    public function test_picks_all_five_parameters_can_be_combined(): void
    {
        $category = Category::factory()->create(['is_active' => true]);
        $storeCategory = StoreCategory::factory()->create(['is_active' => true]);

        // Create a product that matches all filters
        $store = Store::factory()->active()->create(['name' => 'UniqueStoreName']);
        $store->categories()->attach($storeCategory->id);
        $product = $this->createActiveProductWithOffer(
            ['title' => 'UniqueProductTitle', 'store_id' => $store->id, 'base_price' => 100.00],
            [],
            ['percentage_value' => 50, 'type' => ProductOfferType::PERCENTAGE]
        );
        $product->categories()->attach($category->id);

        // Combine all 5 parameters: interest_id, activity_id, search, min_discount_percent, sort_by
        $response = $this->getJson(
            "/api/v1/explore/picks?interest_id={$category->id}"
            . "&activity_id={$storeCategory->id}"
            . "&search=Unique"
            . "&min_discount_percent=30"
            . "&sort_by=highest_discount"
        );

        $response->assertOk();
        $data = $response->json('data');

        $returnedProductIds = collect($data)->pluck('product_id')->toArray();
        $this->assertContains($product->id, $returnedProductIds);
        $this->assertCount(1, $data);

        // Verify pagination metadata is present and valid
        $response->assertJsonStructure([
            'pagination' => ['page', 'page_size', 'total', 'total_pages', 'has_more'],
        ]);
        $this->assertEquals(1, $response->json('pagination.total'));
    }
}
