<?php

namespace App\Domain\FollowingFeed\Services;

use App\Domain\Product\Enums\ProductApprovalStatus;
use App\Domain\Product\Enums\ProductOfferStatus;
use App\Domain\Product\Enums\ProductStatus;
use App\Domain\Product\Models\Product;
use App\Domain\Product\Models\ProductFavorite;
use App\Domain\Product\Models\ProductLike;
use App\Domain\Store\Enums\StoreStatus;
use App\Domain\Store\Models\StoreFollowers;
use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FollowingFeedService
{
    /**
     * Get paginated following feed items using the multi-tier fallback algorithm.
     * Tiers: Followed -> Recommended -> Trending.
     */
    public function getFeedItems(?User $user, int $page, int $perPage, ?float $lat = null, ?float $lng = null): LengthAwarePaginator
    {
        $followedStoreIds = $user ? $this->getFollowedStoreIds($user->id) : [];
        
        $followedCount = $user ? $this->getFollowedProductsCount($followedStoreIds) : 0;
        
        // Exclude products already processed in higher tiers
        $excludedProductIds = [];
        $items = new Collection();
        
        // 1. Followed Tier
        if ($user && $followedCount > 0) {
            $followedOffset = ($page - 1) * $perPage;
            
            if ($followedOffset < $followedCount) {
                $limit = min($perPage, $followedCount - $followedOffset);
                $followedProducts = $this->getFollowedProducts($followedStoreIds, $limit, $followedOffset);
                
                foreach ($followedProducts as $product) {
                    $product->source_type = 'followed';
                    $product->recommendation_reason = null;
                    $items->push($product);
                    $excludedProductIds[] = $product->id;
                }
            }
        }
        
        // 2. Recommended Tier
        $recommendedCount = $user ? $this->getRecommendedProductsCount($user, $followedStoreIds, $excludedProductIds) : 0;
        
        if ($user && $items->count() < $perPage && $recommendedCount > 0) {
            $remainingLimit = $perPage - $items->count();
            // Calculate virtual page/offset for the recommended tier
            $recommendedOffset = max(0, ($page - 1) * $perPage - $followedCount);
            
            if ($recommendedOffset < $recommendedCount) {
                $recommendedProducts = $this->getRecommendedProducts($user, $followedStoreIds, $excludedProductIds, $remainingLimit, $recommendedOffset);
                
                foreach ($recommendedProducts as $product) {
                    $product->source_type = 'recommended';
                    $product->recommendation_reason = $product->recommendation_reason ?? 'based_on_interests';
                    $items->push($product);
                    $excludedProductIds[] = $product->id;
                }
            }
        }
        
        // 3. Trending Tier
        $trendingCount = $this->getTrendingProductsCount($excludedProductIds);
        
        if ($items->count() < $perPage && $trendingCount > 0) {
            $remainingLimit = $perPage - $items->count();
            // Calculate virtual page/offset for the trending tier
            $trendingOffset = max(0, ($page - 1) * $perPage - $followedCount - $recommendedCount);
            
            if ($trendingOffset < $trendingCount) {
                $trendingProducts = $this->getTrendingProducts($excludedProductIds, $remainingLimit, $trendingOffset, $lat, $lng);
                
                foreach ($trendingProducts as $product) {
                    $product->source_type = 'trending';
                    // Apply popular_nearby if coords are provided, otherwise just trending
                    $product->recommendation_reason = ($lat !== null && $lng !== null) ? 'popular_nearby' : null;
                    $items->push($product);
                    $excludedProductIds[] = $product->id;
                }
            }
        }
        
        // Total items is the sum of all available active items across the tiers
        $totalItems = $followedCount + $recommendedCount + $trendingCount;
        
        // Prepare social metrics for the current page items
        $this->resolveSocialMetrics($user, $items, $followedStoreIds);
        
        return new LengthAwarePaginator(
            $items,
            $totalItems,
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );
    }
    
    /**
     * Get IDs of stores the user follows.
     */
    private function getFollowedStoreIds(string $userId): array
    {
        return StoreFollowers::where('user_id', $userId)
            ->pluck('store_id')
            ->toArray();
    }
    
    /**
     * Base query for active products with active offers from active stores.
     */
    private function baseProductQuery(): Builder
    {
        return Product::query()
            ->where('products.status', ProductStatus::ACTIVE)
            ->where('products.approval_status', ProductApprovalStatus::APPROVED)
            ->whereHas('store', fn (Builder $q) => $q->where('stores.status', StoreStatus::ACTIVE))
            ->whereHas('offer', function (Builder $q) {
                $q->where('product_offers.status', ProductOfferStatus::ACTIVE)
                    ->where(function (Builder $dateQ) {
                        $dateQ->whereNull('product_offers.starts_at')
                            ->orWhere('product_offers.starts_at', '<=', now());
                    })
                    ->where(function (Builder $dateQ) {
                        $dateQ->whereNull('product_offers.ends_at')
                            ->orWhere('product_offers.ends_at', '>', now());
                    });
            });
    }
    
    /**
     * Base query tailored for fetching feed data with necessary joins.
     */
    private function feedQuery(): Builder
    {
        return $this->baseProductQuery()
            ->join('product_offers', function ($join) {
                $join->on('product_offers.product_id', '=', 'products.id')
                    ->where('product_offers.status', ProductOfferStatus::ACTIVE->value);
            })
            ->join('stores', 'stores.id', '=', 'products.store_id')
            ->with(['images', 'categories'])
            ->selectRaw(
                'products.*, product_offers.id as offer_id, product_offers.label as offer_label, '
                . 'product_offers.type as offer_type, product_offers.percentage_value, '
                . 'product_offers.fixed_amount, product_offers.ends_at as offer_ends_at, '
                . 'stores.name as store_name, stores.logo_url as store_image_url'
            );
    }
    
    /**
     * Tier 1: Followed Products count.
     */
    private function getFollowedProductsCount(array $followedStoreIds): int
    {
        if (empty($followedStoreIds)) {
            return 0;
        }
        
        return $this->baseProductQuery()
            ->whereIn('products.store_id', $followedStoreIds)
            ->count();
    }
    
    /**
     * Tier 1: Followed Products.
     */
    private function getFollowedProducts(array $followedStoreIds, int $limit, int $offset): Collection
    {
        if (empty($followedStoreIds)) {
            return new Collection();
        }
        
        return $this->feedQuery()
            ->whereIn('products.store_id', $followedStoreIds)
            ->orderByDesc('products.created_at')
            ->offset($offset)
            ->limit($limit)
            ->get();
    }
    
    /**
     * Get user interest category IDs from onboarding, favorites, and followed stores.
     */
    private function getUserInterestCategoryIds(User $user, array $followedStoreIds): array
    {
        $categoryIds = [];
        
        // 1. From onboarding interests
        $interests = DB::table('interests')->where('user_id', $user->id)->first();
        if ($interests && !empty($interests->interesting_offers)) {
            $offerSlugs = json_decode($interests->interesting_offers, true) ?? [];
            if (!empty($offerSlugs)) {
                $ids = DB::table('categories')
                    ->whereIn('slug', $offerSlugs)
                    ->pluck('id')
                    ->toArray();
                $categoryIds = array_merge($categoryIds, $ids);
            }
        }
        
        // 2. From product favorites
        $favoriteIds = DB::table('product_favorites')
            ->where('user_id', $user->id)
            ->pluck('product_id')
            ->toArray();
            
        if (!empty($favoriteIds)) {
            $ids = DB::table('product_categories')
                ->whereIn('product_id', $favoriteIds)
                ->pluck('category_id')
                ->toArray();
            $categoryIds = array_merge($categoryIds, $ids);
        }
        
        // 3. From followed stores
        if (!empty($followedStoreIds)) {
            $ids = DB::table('store_store_category')
                ->join('store_categories', 'store_categories.id', '=', 'store_store_category.store_category_id')
                ->whereIn('store_store_category.store_id', $followedStoreIds)
                ->pluck('store_categories.id') // these are store categories, might map to product categories differently but we'll use them if they match
                ->toArray();
            // In this system, product categories and store categories are separate tables.
            // We'll primarily rely on product_categories for filtering products directly.
            // We skip adding store category IDs directly to the product category array.
        }
        
        return array_unique($categoryIds);
    }
    
    /**
     * Get user interested store category IDs.
     */
    private function getUserInterestStoreCategoryIds(array $followedStoreIds): array
    {
        if (empty($followedStoreIds)) {
            return [];
        }
        
        return DB::table('store_store_category')
            ->whereIn('store_id', $followedStoreIds)
            ->pluck('store_category_id')
            ->toArray();
    }
    
    /**
     * Apply recommended filters.
     */
    private function applyRecommendedFilters(Builder $query, User $user, array $followedStoreIds, array $excludedProductIds): void
    {
        if (!empty($followedStoreIds)) {
            $query->whereNotIn('products.store_id', $followedStoreIds);
        }
        
        if (!empty($excludedProductIds)) {
            $query->whereNotIn('products.id', $excludedProductIds);
        }
        
        $productCatIds = $this->getUserInterestCategoryIds($user, $followedStoreIds);
        $storeCatIds = $this->getUserInterestStoreCategoryIds($followedStoreIds);
        
        if (!empty($productCatIds) || !empty($storeCatIds)) {
            $query->where(function (Builder $q) use ($productCatIds, $storeCatIds) {
                if (!empty($productCatIds)) {
                    $q->orWhereExists(function ($sub) use ($productCatIds) {
                        $sub->select(DB::raw(1))
                            ->from('product_categories')
                            ->whereColumn('product_categories.product_id', 'products.id')
                            ->whereIn('product_categories.category_id', $productCatIds);
                    });
                }
                
                if (!empty($storeCatIds)) {
                    $q->orWhereExists(function ($sub) use ($storeCatIds) {
                        $sub->select(DB::raw(1))
                            ->from('store_store_category')
                            ->whereColumn('store_store_category.store_id', 'products.store_id')
                            ->whereIn('store_store_category.store_category_id', $storeCatIds);
                    });
                }
            });
        }
    }
    
    /**
     * Tier 2: Recommended Products count.
     */
    private function getRecommendedProductsCount(User $user, array $followedStoreIds, array $excludedProductIds): int
    {
        $query = $this->baseProductQuery();
        $this->applyRecommendedFilters($query, $user, $followedStoreIds, $excludedProductIds);
        return $query->count();
    }
    
    /**
     * Tier 2: Recommended Products.
     */
    private function getRecommendedProducts(User $user, array $followedStoreIds, array $excludedProductIds, int $limit, int $offset): Collection
    {
        $query = $this->feedQuery();
        $this->applyRecommendedFilters($query, $user, $followedStoreIds, $excludedProductIds);
        
        // Shuffle slightly to avoid repetition (using consistent random order based on date for pagination stability)
        $seed = date('YmdH');
        $driver = DB::getDriverName();
        if ($driver === 'mysql' || $driver === 'pgsql') {
            $query->orderByRaw("RAND({$seed})");
        } else {
            $query->inRandomOrder($seed);
        }
        
        $products = $query->offset($offset)->limit($limit)->get();
        
        // Determine recommendation reason
        $storeCatIds = $this->getUserInterestStoreCategoryIds($followedStoreIds);
        
        foreach ($products as $product) {
            $productStoreCatIds = DB::table('store_store_category')
                ->where('store_id', $product->store_id)
                ->pluck('store_category_id')
                ->toArray();
                
            $intersect = array_intersect($storeCatIds, $productStoreCatIds);
            
            if (!empty($intersect)) {
                $product->recommendation_reason = 'similar_to_followed';
            } else {
                $product->recommendation_reason = 'based_on_interests';
            }
        }
        
        return $products;
    }
    
    /**
     * Apply trending filters.
     */
    private function applyTrendingFilters(Builder $query, array $excludedProductIds): void
    {
        if (!empty($excludedProductIds)) {
            $query->whereNotIn('products.id', $excludedProductIds);
        }
    }
    
    /**
     * Tier 3: Trending Products count.
     */
    private function getTrendingProductsCount(array $excludedProductIds): int
    {
        $query = $this->baseProductQuery();
        $this->applyTrendingFilters($query, $excludedProductIds);
        return $query->count();
    }
    
    /**
     * Tier 3: Trending Products.
     */
    private function getTrendingProducts(array $excludedProductIds, int $limit, int $offset, ?float $lat, ?float $lng): Collection
    {
        $query = $this->feedQuery();
        $this->applyTrendingFilters($query, $excludedProductIds);
        
        $driver = DB::getDriverName();
        
        // If coords provided, factor in distance (popular_nearby)
        if ($lat !== null && $lng !== null) {
            $storeOwnerType = 'App\\Domain\\Store\\Models\\Store';
            
            $haversineSql = "(6371 * ACOS(LEAST(1, GREATEST(-1,
                COS(RADIANS(?)) * COS(RADIANS(addresses.latitude))
                * COS(RADIANS(addresses.longitude) - RADIANS(?))
                + SIN(RADIANS(?)) * SIN(RADIANS(addresses.latitude))
            ))))";
            
            $query->join('addressables', function ($join) use ($storeOwnerType) {
                $join->on('addressables.owner_id', '=', 'stores.id')
                    ->where('addressables.owner_type', $storeOwnerType);
            })
            ->join('addresses', 'addresses.id', '=', 'addressables.address_id')
            ->whereNotNull('addresses.latitude')
            ->whereNotNull('addresses.longitude')
            ->selectRaw("{$haversineSql} AS distance_km", [$lat, $lng, $lat])
            // Order by distance ascending, then favorites
            ->orderBy('distance_km', 'asc')
            ->orderByDesc('products.favorites_count');
        } else {
            // Standard trending: favorites_count + recent views logic
            if ($driver === 'sqlite') {
                $viewsSubquery = "(SELECT COUNT(*) FROM product_views WHERE product_views.product_id = products.id AND product_views.created_at >= datetime('now', '-7 days'))";
            } else {
                $viewsSubquery = "(SELECT COUNT(*) FROM product_views WHERE product_views.product_id = products.id AND product_views.created_at >= NOW() - INTERVAL 7 DAY)";
            }
            
            $trendingScoreSql = "(
                products.favorites_count * 1
                + {$viewsSubquery} * 0.5
            )";
            
            $query->selectRaw("{$trendingScoreSql} AS trending_score")
                ->orderByDesc('trending_score');
        }
        
        return $query->offset($offset)->limit($limit)->get();
    }
    
    /**
     * Resolve social metrics (is_liked, likes_count, comments_count, is_saved)
     * and store following status for a batch of products.
     */
    private function resolveSocialMetrics(?User $user, Collection $products, array $followedStoreIds): void
    {
        if ($products->isEmpty()) {
            return;
        }
        
        $productIds = $products->pluck('id')->toArray();
        
        $likedProductIds = [];
        $favoritedProductIds = [];
        
        if ($user) {
            // 1. Get user's liked products in this batch
            $likedProductIds = ProductLike::where('user_id', $user->id)
                ->whereIn('product_id', $productIds)
                ->pluck('product_id')
                ->toArray();
                
            // 2. Get user's favorited products in this batch
            $favoritedProductIds = ProductFavorite::where('user_id', $user->id)
                ->whereIn('product_id', $productIds)
                ->pluck('product_id')
                ->toArray();
        }
            
        // 3. Get likes count per product (if not using a denormalized field)
        // For performance, we assume 'favorites_count' is denormalized and available.
        // If we need strict likes count:
        $likesCounts = ProductLike::whereIn('product_id', $productIds)
            ->select('product_id', DB::raw('count(*) as total'))
            ->groupBy('product_id')
            ->pluck('total', 'product_id')
            ->toArray();
            
        // 4. Get comments count per product
        $commentsCounts = DB::table('product_comments')
            ->whereIn('product_id', $productIds)
            ->where('status', 'visible')
            ->select('product_id', DB::raw('count(*) as total'))
            ->groupBy('product_id')
            ->pluck('total', 'product_id')
            ->toArray();
            
        // 5. Apply to products
        foreach ($products as $product) {
            $product->is_liked = in_array($product->id, $likedProductIds);
            $product->is_saved = in_array($product->id, $favoritedProductIds);
            
            $product->likes_count = $likesCounts[$product->id] ?? 0;
            $product->comments_count = $commentsCounts[$product->id] ?? 0;
            
            $product->store_is_followed = in_array($product->store_id, $followedStoreIds);
        }
    }
}
