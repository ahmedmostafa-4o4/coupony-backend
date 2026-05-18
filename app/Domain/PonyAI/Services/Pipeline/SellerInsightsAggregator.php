<?php

namespace App\Domain\PonyAI\Services\Pipeline;

use App\Domain\PonyAI\DTOs\StoreInsightsSnapshot;
use App\Domain\Product\Enums\InventoryMode;
use App\Domain\Product\Enums\ProductApprovalStatus;
use App\Domain\Product\Enums\ProductStatus;
use App\Domain\Product\Models\OfferClaim;
use App\Domain\Product\Models\Product;
use App\Domain\Product\Models\ProductFavorite;
use App\Domain\Product\Models\ProductLike;
use App\Domain\Product\Models\ProductVariant;
use App\Domain\Product\Models\ProductView;
use App\Domain\Store\Models\Store;

/**
 * Pulls KPIs and per-product signals for one store - and ONLY that store.
 *
 * Every query in this class is constrained by the store_id passed in. That
 * store_id comes from the route binding, never from the user's prompt, which
 * is what guarantees the seller assistant cannot see another seller's data.
 */
class SellerInsightsAggregator
{
    private const TOP_PRODUCT_LIMIT = 5;

    private const UNDERPERFORMING_LIMIT = 5;

    private const INVENTORY_WARNING_LIMIT = 5;

    private const UNDERPERFORMING_MIN_VIEWS = 10;

    private const PRODUCT_ID_LIMIT = 50;

    public function snapshot(Store $store): StoreInsightsSnapshot
    {
        $storeId = $store->id;

        $activeProductCount = Product::query()
            ->where('store_id', $storeId)
            ->where('status', ProductStatus::ACTIVE->value)
            ->where('approval_status', ProductApprovalStatus::APPROVED->value)
            ->count();

        $pendingProductCount = Product::query()
            ->where('store_id', $storeId)
            ->where('approval_status', ProductApprovalStatus::PENDING->value)
            ->count();

        $totalViews = ProductView::query()
            ->whereIn('product_id', $this->storeProductIdsSubquery($storeId))
            ->count();

        $totalLikes = ProductLike::query()
            ->whereIn('product_id', $this->storeProductIdsSubquery($storeId))
            ->count();

        $totalFavorites = ProductFavorite::query()
            ->whereIn('product_id', $this->storeProductIdsSubquery($storeId))
            ->count();

        $totalClaims = OfferClaim::query()->where('store_id', $storeId)->count();

        $totalRedemptions = OfferClaim::query()
            ->where('store_id', $storeId)
            ->whereNotNull('redeemed_at')
            ->count();

        $productIds = Product::query()
            ->where('store_id', $storeId)
            ->orderBy('id')
            ->limit(self::PRODUCT_ID_LIMIT)
            ->pluck('id')
            ->all();

        $topProducts = $this->topProducts($storeId);
        $underperforming = $this->underperformingProducts($storeId);
        $inventoryWarnings = $this->inventoryWarnings($storeId);

        return new StoreInsightsSnapshot(
            storeId: (string) $storeId,
            activeProductCount: $activeProductCount,
            pendingProductCount: $pendingProductCount,
            totalViews: $totalViews,
            totalLikes: $totalLikes,
            totalFavorites: $totalFavorites,
            totalClaims: $totalClaims,
            totalRedemptions: $totalRedemptions,
            topProducts: $topProducts,
            underperformingProducts: $underperforming,
            inventoryWarnings: $inventoryWarnings,
            productIds: array_map(static fn ($id): string => (string) $id, $productIds),
        );
    }

    /**
     * Subquery used everywhere we need "products owned by this store".
     */
    private function storeProductIdsSubquery(string $storeId)
    {
        return Product::query()
            ->where('store_id', $storeId)
            ->select('id');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function topProducts(string $storeId): array
    {
        return Product::query()
            ->where('store_id', $storeId)
            ->where('status', ProductStatus::ACTIVE->value)
            ->where('approval_status', ProductApprovalStatus::APPROVED->value)
            ->withCount(['views as views_count', 'offerClaims as claims_count'])
            ->orderByDesc('views_count')
            ->orderByDesc('claims_count')
            ->orderBy('id')
            ->limit(self::TOP_PRODUCT_LIMIT)
            ->get(['id', 'title'])
            ->map(fn (Product $product): array => [
                'id' => (string) $product->id,
                'title' => (string) $product->title,
                'views_count' => (int) ($product->views_count ?? 0),
                'claims_count' => (int) ($product->claims_count ?? 0),
            ])
            ->all();
    }

    /**
     * Products with non-trivial views and zero claims - prime candidates for an offer.
     *
     * @return array<int, array<string, mixed>>
     */
    private function underperformingProducts(string $storeId): array
    {
        return Product::query()
            ->where('store_id', $storeId)
            ->where('status', ProductStatus::ACTIVE->value)
            ->where('approval_status', ProductApprovalStatus::APPROVED->value)
            ->withCount(['views as views_count'])
            ->whereRaw(
                '(select count(*) from product_views where product_views.product_id = products.id) >= ?',
                [self::UNDERPERFORMING_MIN_VIEWS],
            )
            ->whereDoesntHave('offerClaims')
            ->orderByDesc('views_count')
            ->orderBy('id')
            ->limit(self::UNDERPERFORMING_LIMIT)
            ->get(['id', 'title'])
            ->map(fn (Product $product): array => [
                'id' => (string) $product->id,
                'title' => (string) $product->title,
                'views_count' => (int) ($product->views_count ?? 0),
                'claims_count' => 0,
            ])
            ->all();
    }

    /**
     * Products with at least one tracked variant under its low_stock_threshold.
     *
     * @return array<int, array<string, mixed>>
     */
    private function inventoryWarnings(string $storeId): array
    {
        $lowStockVariantsByProduct = ProductVariant::query()
            ->whereIn('product_id', $this->storeProductIdsSubquery($storeId))
            ->where('inventory_mode', InventoryMode::TRACKED->value)
            ->whereNotNull('low_stock_threshold')
            ->whereColumn('stock_qty', '<=', 'low_stock_threshold')
            ->selectRaw('product_id, count(*) as low_stock_variants')
            ->groupBy('product_id')
            ->orderByDesc('low_stock_variants')
            ->limit(self::INVENTORY_WARNING_LIMIT)
            ->get();

        if ($lowStockVariantsByProduct->isEmpty()) {
            return [];
        }

        $products = Product::query()
            ->where('store_id', $storeId)
            ->whereIn('id', $lowStockVariantsByProduct->pluck('product_id'))
            ->get(['id', 'title'])
            ->keyBy('id');

        return $lowStockVariantsByProduct
            ->map(fn ($row): array => [
                'id' => (string) $row->product_id,
                'title' => (string) ($products[$row->product_id]->title ?? ''),
                'low_stock_variants' => (int) $row->low_stock_variants,
            ])
            ->values()
            ->all();
    }
}
