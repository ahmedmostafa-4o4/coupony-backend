<?php

namespace Tests\Unit\PonyAI;

use App\Domain\PonyAI\Services\Pipeline\SellerInsightsAggregator;
use App\Domain\Product\Enums\InventoryMode;
use App\Domain\Product\Enums\OfferClaimStatus;
use App\Domain\Product\Enums\ProductApprovalStatus;
use App\Domain\Product\Enums\ProductStatus;
use App\Domain\Product\Models\OfferClaim;
use App\Domain\Product\Models\Product;
use App\Domain\Product\Models\ProductFavorite;
use App\Domain\Product\Models\ProductLike;
use App\Domain\Product\Models\ProductVariant;
use App\Domain\Product\Models\ProductView;
use App\Domain\Store\Models\Store;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SellerInsightsAggregatorTest extends TestCase
{
    use RefreshDatabase;

    private function makeStore(): Store
    {
        return Store::factory()->create([
            'owner_user_id' => User::factory()->create()->id,
        ]);
    }

    private function product(Store $store, array $attrs = []): Product
    {
        return Product::factory()->create(array_merge([
            'store_id' => $store->id,
            'status' => ProductStatus::ACTIVE,
            'approval_status' => ProductApprovalStatus::APPROVED,
        ], $attrs));
    }

    private function viewProduct(Product $product, int $times = 1): void
    {
        for ($i = 0; $i < $times; $i++) {
            ProductView::create([
                'product_id' => $product->id,
                'ip_address' => '127.0.0.1',
            ]);
        }
    }

    private function claimOffer(Product $product, User $user, ?string $redeemedAt = null): OfferClaim
    {
        return OfferClaim::create([
            'user_id' => $user->id,
            'store_id' => $product->store_id,
            'product_id' => $product->id,
            'offer_id' => $product->offer?->id ?? $product->offer()->firstOrFail()->id,
            'status' => $redeemedAt !== null ? OfferClaimStatus::REDEEMED : OfferClaimStatus::ACTIVE,
            'claim_token' => 'token-'.uniqid(),
            'qr_code_token' => 'qr-'.uniqid(),
            'offer_snapshot' => [],
            'expires_at' => now()->addDay(),
            'redeemed_at' => $redeemedAt,
        ]);
    }

    public function test_snapshot_counts_are_scoped_to_the_store(): void
    {
        $myStore = $this->makeStore();
        $otherStore = $this->makeStore();
        $customer = User::factory()->create();

        $myProduct = $this->product($myStore, ['title' => 'mine']);
        $otherProduct = $this->product($otherStore, ['title' => 'theirs']);

        $this->viewProduct($myProduct, 3);
        $this->viewProduct($otherProduct, 7);

        ProductLike::create(['product_id' => $myProduct->id, 'user_id' => $customer->id]);
        ProductLike::create(['product_id' => $otherProduct->id, 'user_id' => $customer->id]);
        ProductFavorite::create(['product_id' => $myProduct->id, 'user_id' => $customer->id]);

        $this->claimOffer($myProduct, $customer);
        $this->claimOffer($otherProduct, $customer);

        $snapshot = $this->app->make(SellerInsightsAggregator::class)->snapshot($myStore);

        $this->assertSame((string) $myStore->id, $snapshot->storeId);
        $this->assertSame(1, $snapshot->activeProductCount);
        $this->assertSame(3, $snapshot->totalViews);
        $this->assertSame(1, $snapshot->totalLikes);
        $this->assertSame(1, $snapshot->totalFavorites);
        $this->assertSame(1, $snapshot->totalClaims);
        $this->assertSame(0, $snapshot->totalRedemptions);

        $this->assertCount(1, $snapshot->productIds);
        $this->assertSame($myProduct->id, $snapshot->productIds[0]);
    }

    public function test_top_products_orders_by_views_desc_and_scopes_to_store(): void
    {
        $store = $this->makeStore();
        $otherStore = $this->makeStore();

        $popular = $this->product($store, ['title' => 'popular']);
        $quiet = $this->product($store, ['title' => 'quiet']);
        $foreign = $this->product($otherStore, ['title' => 'foreign-bestseller']);

        $this->viewProduct($popular, 10);
        $this->viewProduct($quiet, 2);
        $this->viewProduct($foreign, 1000);

        $snapshot = $this->app->make(SellerInsightsAggregator::class)->snapshot($store);

        $this->assertSame(['popular', 'quiet'], collect($snapshot->topProducts)->pluck('title')->all());
        $this->assertNotContains('foreign-bestseller', collect($snapshot->topProducts)->pluck('title')->all());
    }

    public function test_underperforming_lists_products_with_views_but_zero_claims(): void
    {
        $store = $this->makeStore();

        $watched = $this->product($store, ['title' => 'watched-not-bought']);
        $bought = $this->product($store, ['title' => 'watched-and-bought']);

        $this->viewProduct($watched, 15);
        $this->viewProduct($bought, 15);
        $this->claimOffer($bought, User::factory()->create());

        $snapshot = $this->app->make(SellerInsightsAggregator::class)->snapshot($store);

        $titles = collect($snapshot->underperformingProducts)->pluck('title')->all();
        $this->assertContains('watched-not-bought', $titles);
        $this->assertNotContains('watched-and-bought', $titles);
    }

    public function test_underperforming_threshold_excludes_low_view_products(): void
    {
        $store = $this->makeStore();
        $product = $this->product($store, ['title' => 'barely-viewed']);
        $this->viewProduct($product, 3);

        $snapshot = $this->app->make(SellerInsightsAggregator::class)->snapshot($store);

        $this->assertSame([], $snapshot->underperformingProducts);
    }

    public function test_inventory_warnings_surface_tracked_variants_below_threshold(): void
    {
        $store = $this->makeStore();
        $product = $this->product($store, ['title' => 'low-stock-item']);

        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'inventory_mode' => InventoryMode::TRACKED,
            'stock_qty' => 2,
            'low_stock_threshold' => 5,
            'is_default' => true,
        ]);

        $snapshot = $this->app->make(SellerInsightsAggregator::class)->snapshot($store);

        $this->assertCount(1, $snapshot->inventoryWarnings);
        $this->assertSame('low-stock-item', $snapshot->inventoryWarnings[0]['title']);
        $this->assertSame(1, $snapshot->inventoryWarnings[0]['low_stock_variants']);
    }

    public function test_inventory_warnings_ignore_unlimited_mode(): void
    {
        $store = $this->makeStore();
        $product = $this->product($store, ['title' => 'unlimited']);

        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'inventory_mode' => InventoryMode::UNLIMITED,
            'stock_qty' => null,
            'low_stock_threshold' => null,
            'is_default' => true,
        ]);

        $snapshot = $this->app->make(SellerInsightsAggregator::class)->snapshot($store);

        $this->assertSame([], $snapshot->inventoryWarnings);
    }

    public function test_redemptions_count_only_redeemed_claims(): void
    {
        $store = $this->makeStore();
        $customer = User::factory()->create();
        $product = $this->product($store);

        $this->claimOffer($product, $customer, '2026-05-10 12:00:00');
        $this->claimOffer($product, $customer);

        $snapshot = $this->app->make(SellerInsightsAggregator::class)->snapshot($store);

        $this->assertSame(2, $snapshot->totalClaims);
        $this->assertSame(1, $snapshot->totalRedemptions);
    }
}
