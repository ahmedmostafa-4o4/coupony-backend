<?php

namespace App\Domain\Explore\Services;

use App\Domain\Explore\Support\DiscountCalculator;
use App\Domain\Product\Enums\ProductApprovalStatus;
use App\Domain\Product\Enums\ProductOfferStatus;
use App\Domain\Product\Enums\ProductStatus;
use App\Domain\Product\Models\Category;
use App\Domain\Product\Models\Product;
use App\Domain\Product\Models\ProductFavorite;
use App\Domain\Product\Services\ProductRecommendationService;
use App\Domain\Store\Enums\StoreStatus;
use App\Domain\Store\Models\StoreCategory;
use App\Domain\User\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ExploreService
{
    public function __construct(
        private readonly ProductRecommendationService $recommendationService,
    ) {}

    /**
     * Base query scope for active products with active offers from active stores.
     *
     * Filters:
     * - Products with status = active and approval_status = approved
     * - Products belonging to stores with status = active
     * - Products that have at least one offer with status = active
     *   AND (starts_at IS NULL OR starts_at <= NOW())
     *   AND (ends_at IS NULL OR ends_at > NOW())
     */
    public function baseProductQuery(): Builder
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
     * Get personalized products based on the user's onboarding interests.
     *
     * Uses the customer's interesting_offers (store category slugs),
     * budget, and shopping_style to find relevant active products.
     *
     * For guests or users without onboarding data, falls back to popular products.
     */
    public function getInterests(?User $user, int $limit = 10): Collection
    {
        // Load user's onboarding interests
        $interests = $user
            ? DB::table('interests')->where('user_id', $user->id)->first()
            : null;

        $query = $this->baseProductQuery()
            ->join('product_offers', function ($join) {
                $join->on('product_offers.product_id', '=', 'products.id')
                    ->where('product_offers.status', ProductOfferStatus::ACTIVE->value);
            })
            ->join('stores', 'stores.id', '=', 'products.store_id');

        if ($interests) {
            // Filter by interesting_offers (store category slugs)
            $offerSlugs = json_decode($interests->interesting_offers, true) ?? [];
            if (! empty($offerSlugs)) {
                $query->whereExists(function ($sub) use ($offerSlugs) {
                    $sub->select(DB::raw(1))
                        ->from('store_store_category')
                        ->join('store_categories', 'store_categories.id', '=', 'store_store_category.store_category_id')
                        ->whereColumn('store_store_category.store_id', 'products.store_id')
                        ->whereIn('store_categories.slug', $offerSlugs);
                });
            }

            // Filter by budget (price range)
            $this->applyBudgetFilter($query, $interests->budget);

            // Sort by shopping_style preference
            $shoppingStyles = json_decode($interests->shopping_style, true) ?? [];
            $this->applyShoppingStyleSort($query, $shoppingStyles);
        } else {
            // Guest fallback: popular products by favorites_count
            $query->orderByDesc('products.favorites_count');
        }

        $products = $query
            ->selectRaw(
                'products.*, product_offers.id as offer_id, product_offers.label as offer_label, '
                . 'product_offers.type as offer_type, product_offers.percentage_value, '
                . 'product_offers.fixed_amount, stores.name as store_name'
            )
            ->limit($limit)
            ->get();

        $favoritedProductIds = $this->getUserFavoritedProductIds($user, $products->pluck('id')->toArray());

        return $products->map(fn ($product) => $this->mapTrendingOfferResponse($product, $favoritedProductIds));
    }

    /**
     * Apply budget-based price filtering.
     *
     * low: base_price <= 100
     * medium: base_price between 100 and 500
     * best_value: prioritize highest discount (no price cap)
     */
    private function applyBudgetFilter(Builder $query, ?string $budget): void
    {
        match ($budget) {
            'low' => $query->where('products.base_price', '<=', 100),
            'medium' => $query->whereBetween('products.base_price', [100, 500]),
            'best_value' => $query->orderByRaw("CASE
                WHEN product_offers.type = 'percentage' THEN COALESCE(product_offers.percentage_value, 0)
                WHEN product_offers.type = 'fixed' AND products.base_price > 0 THEN (COALESCE(product_offers.fixed_amount, 0) / products.base_price) * 100
                ELSE 0
            END DESC"),
            default => null,
        };
    }

    /**
     * Apply sorting based on shopping style preferences.
     *
     * best_discount / based_on_offer: sort by highest discount
     * online / in_store: sort by popularity (favorites_count)
     */
    private function applyShoppingStyleSort(Builder $query, array $shoppingStyles): void
    {
        if (in_array('best_discount', $shoppingStyles) || in_array('based_on_offer', $shoppingStyles)) {
            $query->orderByRaw("CASE
                WHEN product_offers.type = 'percentage' THEN COALESCE(product_offers.percentage_value, 0)
                WHEN product_offers.type = 'fixed' AND products.base_price > 0 THEN (COALESCE(product_offers.fixed_amount, 0) / products.base_price) * 100
                ELSE 0
            END DESC");
        } else {
            $query->orderByDesc('products.favorites_count');
        }
    }

    /**
     * Get all active store categories (activities) with id, name, and icon_url.
     */
    public function getActivities(): Collection
    {
        return StoreCategory::active()
            ->get(['id', 'name_ar', 'name_en', 'icon_url'])
            ->map(fn (StoreCategory $storeCategory) => [
                'id' => $storeCategory->id,
                'name' => $storeCategory->name,
                'icon_url' => $storeCategory->icon_url,
            ]);
    }

    /**
     * Get trending offers sorted by trending score.
     *
     * Calculates a trending score using:
     * campaign_priority * 3 + favorites_count * 1 + views_last_7_days * 0.5
     * + discount_percent * 0.2 + recency_score
     *
     * Since campaign_priority column does not exist, it defaults to 0.
     */
    public function getTrendingOffers(array $filters, ?User $user, int $limit = 10): Collection
    {
        $query = $this->baseProductQuery()
            ->join('product_offers', function ($join) {
                $join->on('product_offers.product_id', '=', 'products.id')
                    ->where('product_offers.status', ProductOfferStatus::ACTIVE->value);
            })
            ->join('stores', 'stores.id', '=', 'products.store_id');

        // Apply filters
        $this->applyTrendingFilters($query, $filters);

        // Calculate discount_percent as a SQL expression for the trending score
        $discountPercentExpr = "CASE
            WHEN product_offers.type = 'percentage' THEN COALESCE(product_offers.percentage_value, 0)
            WHEN product_offers.type = 'fixed' AND products.base_price > 0 THEN (COALESCE(product_offers.fixed_amount, 0) / products.base_price) * 100
            ELSE 0
        END";

        // Trending score SQL calculation (database-agnostic)
        $driver = DB::getDriverName();
        if ($driver === 'sqlite') {
            $viewsSubquery = "(SELECT COUNT(*) FROM product_views WHERE product_views.product_id = products.id AND product_views.created_at >= datetime('now', '-7 days'))";
            $recencyExpr = "MAX(30 - CAST((julianday('now') - julianday(product_offers.created_at)) AS INTEGER), 0)";
        } else {
            $viewsSubquery = "(SELECT COUNT(*) FROM product_views WHERE product_views.product_id = products.id AND product_views.created_at >= NOW() - INTERVAL 7 DAY)";
            $recencyExpr = "GREATEST(30 - DATEDIFF(NOW(), product_offers.created_at), 0)";
        }

        $trendingScoreSql = "(
            0 * 3
            + products.favorites_count * 1
            + {$viewsSubquery} * 0.5
            + ({$discountPercentExpr}) * 0.2
            + {$recencyExpr}
        )";

        $query->selectRaw("products.*, product_offers.id as offer_id, product_offers.label as offer_label, product_offers.type as offer_type, product_offers.percentage_value, product_offers.fixed_amount, product_offers.created_at as offer_created_at, stores.name as store_name, {$trendingScoreSql} AS trending_score")
            ->orderByDesc('trending_score')
            ->limit($limit);

        $products = $query->get();

        // Get user's favorited product IDs for is_favorite resolution
        $favoritedProductIds = $this->getUserFavoritedProductIds($user, $products->pluck('id')->toArray());

        return $products->map(function ($product) use ($favoritedProductIds) {
            return $this->mapTrendingOfferResponse($product, $favoritedProductIds);
        });
    }

    /**
     * Apply interest_id, activity_id, and search filters to the trending query.
     */
    private function applyTrendingFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['interest_id'])) {
            $query->whereExists(function ($sub) use ($filters) {
                $sub->select(DB::raw(1))
                    ->from('product_categories')
                    ->whereColumn('product_categories.product_id', 'products.id')
                    ->where('product_categories.category_id', $filters['interest_id']);
            });
        }

        if (! empty($filters['activity_id'])) {
            $query->whereExists(function ($sub) use ($filters) {
                $sub->select(DB::raw(1))
                    ->from('store_store_category')
                    ->whereColumn('store_store_category.store_id', 'products.store_id')
                    ->where('store_store_category.store_category_id', $filters['activity_id']);
            });
        }

        if (! empty($filters['search'])) {
            $searchTerm = '%' . $filters['search'] . '%';
            $query->where(function (Builder $q) use ($searchTerm) {
                $q->where('product_offers.label', 'LIKE', $searchTerm)
                    ->orWhere('products.title', 'LIKE', $searchTerm)
                    ->orWhere('stores.name', 'LIKE', $searchTerm);
            });
        }
    }

    /**
     * Get the set of product IDs that the user has favorited.
     */
    private function getUserFavoritedProductIds(?User $user, array $productIds): array
    {
        if (! $user || empty($productIds)) {
            return [];
        }

        return ProductFavorite::where('user_id', $user->id)
            ->whereIn('product_id', $productIds)
            ->pluck('product_id')
            ->toArray();
    }

    /**
     * Map a trending product row to the response format.
     */
    private function mapTrendingOfferResponse($product, array $favoritedProductIds): array
    {
        $basePrice = (float) $product->base_price;

        // Load the offer relationship for DiscountCalculator
        $offer = $product->offer;
        if ($offer) {
            [$discountPercent, $discountedPrice] = DiscountCalculator::calculate($offer, $basePrice);
        } else {
            $discountPercent = 0.0;
            $discountedPrice = $basePrice;
        }

        // Get first image by sort_order
        $firstImage = $product->images()->first();
        $imageUrl = $firstImage ? $firstImage->image_url : null;

        // Title: offer label or product title
        $title = $product->offer_label ?: $product->title;

        // interest_id: first category ID from product's categories
        $firstCategory = $product->categories()->first();
        $interestId = $firstCategory ? $firstCategory->id : null;

        // activity_id: first store category ID from store's categories
        $store = $product->store;
        $firstStoreCategory = $store ? $store->categories()->first() : null;
        $activityId = $firstStoreCategory ? $firstStoreCategory->id : null;

        return [
            'id' => $product->offer_id,
            'product_id' => $product->id,
            'store_id' => $product->store_id,
            'image_url' => $imageUrl,
            'title' => $title,
            'store_name' => $product->store_name,
            'discount_percent' => round($discountPercent, 2),
            'original_price' => $basePrice,
            'discounted_price' => $discountedPrice,
            'saved_count' => (int) $product->favorites_count,
            'interest_id' => $interestId,
            'activity_id' => $activityId,
            'is_favorite' => in_array($product->id, $favoritedProductIds),
        ];
    }

    /**
     * Get flash offers expiring within the next 24 hours.
     *
     * Returns offers where ends_at > NOW() and ends_at <= NOW() + 24 hours,
     * sorted by ends_at ascending (soonest-expiring first).
     */
    public function getFlashOffers(array $filters, ?User $user): Collection
    {
        $query = $this->baseProductQuery()
            ->with(['store.categories', 'images', 'offer', 'categories'])
            ->whereHas('offer', function (Builder $q) {
                $q->where('product_offers.status', ProductOfferStatus::ACTIVE);
                $driver = DB::getDriverName();
                if ($driver === 'sqlite') {
                    $q->whereRaw("ends_at > datetime('now')")
                        ->whereRaw("ends_at <= datetime('now', '+24 hours')");
                } else {
                    $q->whereRaw('ends_at > NOW()')
                        ->whereRaw('ends_at <= NOW() + INTERVAL 24 HOUR');
                }
            });

        // Apply interest_id filter
        if (! empty($filters['interest_id'])) {
            $query->whereHas('categories', fn (Builder $q) => $q->where('categories.id', $filters['interest_id']));
        }

        // Apply activity_id filter
        if (! empty($filters['activity_id'])) {
            $query->whereHas('store.categories', fn (Builder $q) => $q->where('store_categories.id', $filters['activity_id']));
        }

        // Apply search filter (case-insensitive on offer label/product title and store name)
        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $q) use ($search) {
                $q->where('title', 'LIKE', "%{$search}%")
                    ->orWhereHas('offer', fn (Builder $oq) => $oq->where('label', 'LIKE', "%{$search}%"))
                    ->orWhereHas('store', fn (Builder $sq) => $sq->where('name', 'LIKE', "%{$search}%"));
            });
        }

        // Sort by ends_at ascending via a join
        $query->join('product_offers', function ($join) {
            $join->on('product_offers.product_id', '=', 'products.id')
                ->where('product_offers.status', ProductOfferStatus::ACTIVE->value);
        })
            ->orderBy('product_offers.ends_at', 'asc')
            ->select('products.*');

        $products = $query->get();

        return $products->map(function (Product $product) {
            $offer = $product->offer;
            $store = $product->store;
            $basePrice = (float) $product->base_price;

            [$discountPercent] = DiscountCalculator::calculate($offer, $basePrice);

            $primaryImage = $product->images->first();
            $title = $offer->label ?: $product->title;
            $interestId = $product->categories->first()?->id ?? null;
            $activityId = $store->categories->first()?->id ?? null;

            return [
                'id' => $offer->id,
                'product_id' => $product->id,
                'store_id' => $product->store_id,
                'image_url' => $primaryImage?->image_url,
                'title' => $title,
                'store_name' => $store->name,
                'discount_percent' => round($discountPercent, 2),
                'expires_at' => $offer->ends_at?->toIso8601String(),
                'interest_id' => $interestId,
                'activity_id' => $activityId,
            ];
        });
    }

    /**
     * Get top-rated stores with their best coupon.
     *
     * Queries stores that have at least one active product with an active offer,
     * sorted by rating_avg descending. For each store, selects the best coupon
     * (highest discount active offer).
     *
     * Filters: interest_id, activity_id, search (all nullable)
     */
    public function getTopStores(array $filters, int $limit = 10): Collection
    {
        $interestId = $filters['interest_id'] ?? null;
        $activityId = $filters['activity_id'] ?? null;
        $search = $filters['search'] ?? null;

        $query = \App\Domain\Store\Models\Store::query()
            ->where('status', StoreStatus::ACTIVE)
            ->whereHas('products', function (Builder $q) use ($interestId) {
                $q->where('status', \App\Domain\Product\Enums\ProductStatus::ACTIVE)
                    ->where('approval_status', \App\Domain\Product\Enums\ProductApprovalStatus::APPROVED)
                    ->whereHas('offer', fn (Builder $offerQ) => $offerQ->where('status', ProductOfferStatus::ACTIVE));

                if ($interestId) {
                    $q->whereHas('categories', fn (Builder $catQ) => $catQ->where('categories.id', $interestId));
                }
            });

        // Filter by activity_id (store category)
        if ($activityId) {
            $query->whereHas('categories', fn (Builder $q) => $q->where('store_categories.id', $activityId));
        }

        // Filter by search (store name, product offer label, or product title)
        if ($search) {
            $searchTerm = '%' . $search . '%';
            $query->where(function (Builder $q) use ($searchTerm) {
                $q->where('name', 'LIKE', $searchTerm)
                    ->orWhereHas('products', function (Builder $pq) use ($searchTerm) {
                        $pq->where('status', \App\Domain\Product\Enums\ProductStatus::ACTIVE)
                            ->where('approval_status', \App\Domain\Product\Enums\ProductApprovalStatus::APPROVED)
                            ->where(function (Builder $inner) use ($searchTerm) {
                                $inner->where('title', 'LIKE', $searchTerm)
                                    ->orWhereHas('offer', fn (Builder $oq) => $oq->where('label', 'LIKE', $searchTerm));
                            });
                    });
            });
        }

        $stores = $query
            ->orderByDesc('rating_avg')
            ->limit($limit)
            ->get();

        return $stores->map(function (\App\Domain\Store\Models\Store $store) {
            // Find the best coupon: active offer with highest discount
            $bestCoupon = $this->findBestCouponForStore($store);

            // Get interest_id: first category ID from any of the store's products' categories
            $interestId = $store->products()
                ->where('status', \App\Domain\Product\Enums\ProductStatus::ACTIVE)
                ->where('approval_status', \App\Domain\Product\Enums\ProductApprovalStatus::APPROVED)
                ->whereHas('offer', fn (Builder $q) => $q->where('status', ProductOfferStatus::ACTIVE))
                ->with('categories')
                ->first()
                ?->categories
                ?->first()
                ?->id;

            // Get activity_id: first store category ID
            $activityId = $store->categories()->first()?->id;

            return [
                'id' => $store->id,
                'store_id' => $store->id,
                'name' => $store->name,
                'image_url' => $store->logo_url,
                'followers_count' => $store->followers_count ?? 0,
                'rating' => (float) ($store->rating_avg ?? 0),
                'interest_id' => $interestId,
                'activity_id' => $activityId,
                'best_coupon_title' => $bestCoupon['title'] ?? null,
                'best_coupon_discount' => $bestCoupon['discount'] ?? null,
                'best_coupon_image_url' => $bestCoupon['image_url'] ?? null,
            ];
        });
    }

    /**
     * Find the best coupon (highest discount active offer) for a store.
     */
    private function findBestCouponForStore(\App\Domain\Store\Models\Store $store): array
    {
        $activeProducts = $store->products()
            ->where('status', \App\Domain\Product\Enums\ProductStatus::ACTIVE)
            ->where('approval_status', \App\Domain\Product\Enums\ProductApprovalStatus::APPROVED)
            ->whereHas('offer', fn (Builder $q) => $q->where('status', ProductOfferStatus::ACTIVE))
            ->with(['offer', 'images'])
            ->get();

        $bestDiscount = -1;
        $bestCoupon = ['title' => null, 'discount' => null, 'image_url' => null];

        foreach ($activeProducts as $product) {
            $offer = $product->offer;
            if (!$offer || $offer->status !== ProductOfferStatus::ACTIVE) {
                continue;
            }

            $basePrice = (float) $product->base_price;
            [$discountPercent] = DiscountCalculator::calculate($offer, $basePrice);

            if ($discountPercent > $bestDiscount) {
                $bestDiscount = $discountPercent;
                $primaryImage = $product->images->first();

                $bestCoupon = [
                    'title' => $offer->label ?? $product->title,
                    'discount' => round($discountPercent, 2),
                    'image_url' => $primaryImage?->image_url,
                ];
            }
        }

        return $bestCoupon;
    }

    /**
     * Get nearby offers sorted by distance.
     *
     * Uses the Haversine formula in SQL to calculate distance_km between
     * the user's coordinates and store addresses. Joins through the
     * addressables polymorphic table to find store locations.
     *
     * Returns empty collection when lat/lng are not provided (handled by caller).
     */
    public function getNearbyOffers(array $filters, ?User $user, float $lat, float $lng, int $limit = 15): Collection
    {
        $storeOwnerType = 'App\\Domain\\Store\\Models\\Store';

        // Haversine formula for distance in kilometers
        // LEAST/GREATEST clamps the ACOS argument to [-1,1] to avoid NULL from floating-point rounding
        $haversineSql = "(6371 * ACOS(LEAST(1, GREATEST(-1,
            COS(RADIANS(?)) * COS(RADIANS(addresses.latitude))
            * COS(RADIANS(addresses.longitude) - RADIANS(?))
            + SIN(RADIANS(?)) * SIN(RADIANS(addresses.latitude))
        ))))";

        $query = Product::query()
            ->where('products.status', ProductStatus::ACTIVE)
            ->where('products.approval_status', ProductApprovalStatus::APPROVED)
            ->whereNull('products.deleted_at')
            ->join('product_offers', function ($join) {
                $join->on('product_offers.product_id', '=', 'products.id')
                    ->where('product_offers.status', ProductOfferStatus::ACTIVE->value);
            })
            ->join('stores', function ($join) {
                $join->on('stores.id', '=', 'products.store_id')
                    ->where('stores.status', StoreStatus::ACTIVE->value);
            })
            ->join('addressables', function ($join) use ($storeOwnerType) {
                $join->on('addressables.owner_id', '=', 'stores.id')
                    ->where('addressables.owner_type', $storeOwnerType);
            })
            ->join('addresses', 'addresses.id', '=', 'addressables.address_id')
            ->whereNotNull('addresses.latitude')
            ->whereNotNull('addresses.longitude');

        // Apply filters
        $this->applyNearbyFilters($query, $filters);

        $query->selectRaw(
            "products.*, product_offers.id as offer_id, product_offers.label as offer_label, "
            . "product_offers.type as offer_type, product_offers.percentage_value, product_offers.fixed_amount, "
            . "stores.name as store_name, {$haversineSql} AS distance_km",
            [$lat, $lng, $lat]
        )
            ->orderBy('distance_km', 'asc')
            ->limit($limit);

        $products = $query->get();

        // Get user's favorited product IDs
        $favoritedProductIds = $this->getUserFavoritedProductIds($user, $products->pluck('id')->toArray());

        return $products->map(function ($product) use ($favoritedProductIds) {
            return $this->mapNearbyOfferResponse($product, $favoritedProductIds);
        });
    }

    /**
     * Apply interest_id, activity_id, and search filters to the nearby query.
     */
    private function applyNearbyFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['interest_id'])) {
            $query->whereExists(function ($sub) use ($filters) {
                $sub->select(DB::raw(1))
                    ->from('product_categories')
                    ->whereColumn('product_categories.product_id', 'products.id')
                    ->where('product_categories.category_id', $filters['interest_id']);
            });
        }

        if (! empty($filters['activity_id'])) {
            $query->whereExists(function ($sub) use ($filters) {
                $sub->select(DB::raw(1))
                    ->from('store_store_category')
                    ->whereColumn('store_store_category.store_id', 'products.store_id')
                    ->where('store_store_category.store_category_id', $filters['activity_id']);
            });
        }

        if (! empty($filters['search'])) {
            $searchTerm = '%' . $filters['search'] . '%';
            $query->where(function (Builder $q) use ($searchTerm) {
                $q->where('product_offers.label', 'LIKE', $searchTerm)
                    ->orWhere('products.title', 'LIKE', $searchTerm)
                    ->orWhere('stores.name', 'LIKE', $searchTerm);
            });
        }
    }

    /**
     * Map a nearby product row to the response format.
     */
    private function mapNearbyOfferResponse($product, array $favoritedProductIds): array
    {
        $basePrice = (float) $product->base_price;

        // Load the offer relationship for DiscountCalculator
        $offer = $product->offer;
        if ($offer) {
            [$discountPercent, $discountedPrice] = DiscountCalculator::calculate($offer, $basePrice);
        } else {
            $discountPercent = 0.0;
            $discountedPrice = $basePrice;
        }

        // Get first image by sort_order
        $firstImage = $product->images()->first();
        $imageUrl = $firstImage?->image_url;

        // Title: offer label or product title
        $title = $product->offer_label ?: $product->title;

        // interest_id: first category ID from product's categories
        $firstCategory = $product->categories()->first();
        $interestId = $firstCategory?->id;

        // activity_id: first store category ID from store's categories
        $store = $product->store;
        $firstStoreCategory = $store ? $store->categories()->first() : null;
        $activityId = $firstStoreCategory?->id;

        return [
            'id' => $product->offer_id,
            'product_id' => $product->id,
            'store_id' => $product->store_id,
            'image_url' => $imageUrl,
            'title' => $title,
            'store_name' => $product->store_name,
            'original_price' => $basePrice,
            'discounted_price' => $discountedPrice,
            'save_percent' => round($discountPercent, 2),
            'distance_km' => round((float) $product->distance_km, 2),
            'interest_id' => $interestId,
            'activity_id' => $activityId,
        ];
    }

    /**
     * Get paginated picked-for-you offers with filtering and sorting.
     *
     * For authenticated users: uses ProductRecommendationService to get personalized recommendations.
     * For guest users: falls back to popular active products sorted by favorites_count.
     *
     * Applies filters: interest_id, activity_id, search, min_discount_percent
     * Applies sort_by: trending (default), newest, most_saved, highest_discount
     */
    public function getPickedOffers(array $filters, ?User $user, int $page, int $pageSize): LengthAwarePaginator
    {
        $sortBy = $filters['sort_by'] ?? 'trending';

        // Build the base query with active products, active offers, active stores
        $query = $this->baseProductQuery()
            ->join('product_offers', function ($join) {
                $join->on('product_offers.product_id', '=', 'products.id')
                    ->where('product_offers.status', ProductOfferStatus::ACTIVE->value);
            })
            ->join('stores', 'stores.id', '=', 'products.store_id');

        // For authenticated users, prioritize recommended products
        if ($user) {
            $recommendedProducts = $this->recommendationService->recommendFor($user, 200);
            $recommendedIds = $recommendedProducts->pluck('id')->toArray();

            if (!empty($recommendedIds)) {
                // Include recommended products but don't exclude others - use ordering to prioritize
                $query->selectRaw(
                    'products.*, product_offers.id as offer_id, product_offers.label as offer_label, '
                    . 'product_offers.type as offer_type, product_offers.percentage_value, '
                    . 'product_offers.fixed_amount, product_offers.created_at as offer_created_at, '
                    . 'stores.name as store_name'
                );
            } else {
                // No recommendations available, fall back to popular products
                $query->selectRaw(
                    'products.*, product_offers.id as offer_id, product_offers.label as offer_label, '
                    . 'product_offers.type as offer_type, product_offers.percentage_value, '
                    . 'product_offers.fixed_amount, product_offers.created_at as offer_created_at, '
                    . 'stores.name as store_name'
                );
            }
        } else {
            // Guest user: select all active products (will be sorted by popularity)
            $query->selectRaw(
                'products.*, product_offers.id as offer_id, product_offers.label as offer_label, '
                . 'product_offers.type as offer_type, product_offers.percentage_value, '
                . 'product_offers.fixed_amount, product_offers.created_at as offer_created_at, '
                . 'stores.name as store_name'
            );
        }

        // Apply interest_id filter
        if (!empty($filters['interest_id'])) {
            $query->whereExists(function ($sub) use ($filters) {
                $sub->select(DB::raw(1))
                    ->from('product_categories')
                    ->whereColumn('product_categories.product_id', 'products.id')
                    ->where('product_categories.category_id', $filters['interest_id']);
            });
        }

        // Apply activity_id filter
        if (!empty($filters['activity_id'])) {
            $query->whereExists(function ($sub) use ($filters) {
                $sub->select(DB::raw(1))
                    ->from('store_store_category')
                    ->whereColumn('store_store_category.store_id', 'products.store_id')
                    ->where('store_store_category.store_category_id', $filters['activity_id']);
            });
        }

        // Apply search filter (case-insensitive on offer label, product title, store name)
        if (!empty($filters['search'])) {
            $searchTerm = '%' . $filters['search'] . '%';
            $query->where(function (Builder $q) use ($searchTerm) {
                $q->where('product_offers.label', 'LIKE', $searchTerm)
                    ->orWhere('products.title', 'LIKE', $searchTerm)
                    ->orWhere('stores.name', 'LIKE', $searchTerm);
            });
        }

        // Apply min_discount_percent filter
        if (!empty($filters['min_discount_percent']) || (isset($filters['min_discount_percent']) && $filters['min_discount_percent'] === 0)) {
            $minDiscount = (int) $filters['min_discount_percent'];
            if ($minDiscount > 0) {
                $discountExpr = "CASE
                    WHEN product_offers.type = 'percentage' THEN COALESCE(product_offers.percentage_value, 0)
                    WHEN product_offers.type = 'fixed' AND products.base_price > 0 THEN (COALESCE(product_offers.fixed_amount, 0) / products.base_price) * 100
                    ELSE 0
                END";
                $query->whereRaw("({$discountExpr}) >= ?", [$minDiscount]);
            }
        }

        // Apply sorting
        $this->applyPicksSorting($query, $sortBy);

        // Paginate
        $paginator = $query->paginate($pageSize, ['*'], 'page', $page);

        // Get user's favorited product IDs for is_favorite resolution
        $productIds = collect($paginator->items())->pluck('id')->toArray();
        $favoritedProductIds = $this->getUserFavoritedProductIds($user, $productIds);

        // Transform paginator items
        $paginator->getCollection()->transform(function ($product) use ($favoritedProductIds) {
            return $this->mapPickedOfferResponse($product, $favoritedProductIds);
        });

        return $paginator;
    }

    /**
     * Apply sorting to the picks query based on sort_by parameter.
     */
    private function applyPicksSorting(Builder $query, string $sortBy): void
    {
        switch ($sortBy) {
            case 'newest':
                $query->orderByDesc('product_offers.created_at');
                break;

            case 'most_saved':
                $query->orderByDesc('products.favorites_count');
                break;

            case 'highest_discount':
                $discountExpr = "CASE
                    WHEN product_offers.type = 'percentage' THEN COALESCE(product_offers.percentage_value, 0)
                    WHEN product_offers.type = 'fixed' AND products.base_price > 0 THEN (COALESCE(product_offers.fixed_amount, 0) / products.base_price) * 100
                    ELSE 0
                END";
                $query->orderByRaw("({$discountExpr}) DESC");
                break;

            case 'trending':
            default:
                $discountPercentExpr = "CASE
                    WHEN product_offers.type = 'percentage' THEN COALESCE(product_offers.percentage_value, 0)
                    WHEN product_offers.type = 'fixed' AND products.base_price > 0 THEN (COALESCE(product_offers.fixed_amount, 0) / products.base_price) * 100
                    ELSE 0
                END";

                $driver = DB::getDriverName();
                if ($driver === 'sqlite') {
                    $viewsSubquery = "(SELECT COUNT(*) FROM product_views WHERE product_views.product_id = products.id AND product_views.created_at >= datetime('now', '-7 days'))";
                    $recencyExpr = "MAX(30 - CAST((julianday('now') - julianday(product_offers.created_at)) AS INTEGER), 0)";
                } else {
                    $viewsSubquery = "(SELECT COUNT(*) FROM product_views WHERE product_views.product_id = products.id AND product_views.created_at >= NOW() - INTERVAL 7 DAY)";
                    $recencyExpr = "GREATEST(30 - DATEDIFF(NOW(), product_offers.created_at), 0)";
                }

                $trendingScoreSql = "(
                    0 * 3
                    + products.favorites_count * 1
                    + {$viewsSubquery} * 0.5
                    + ({$discountPercentExpr}) * 0.2
                    + {$recencyExpr}
                )";
                $query->orderByRaw("({$trendingScoreSql}) DESC");
                break;
        }
    }

    /**
     * Map a picked product row to the response format.
     */
    private function mapPickedOfferResponse($product, array $favoritedProductIds): array
    {
        $basePrice = (float) $product->base_price;

        // Calculate discount using DiscountCalculator
        $offer = $product->offer;
        if ($offer) {
            [$discountPercent, $discountedPrice] = DiscountCalculator::calculate($offer, $basePrice);
        } else {
            $discountPercent = 0.0;
            $discountedPrice = $basePrice;
        }

        // Get first image by sort_order
        $firstImage = $product->images()->first();
        $imageUrl = $firstImage?->image_url;

        // Title: offer label or product title
        $title = $product->offer_label ?: $product->title;

        // interest_id: first category ID from product's categories
        $firstCategory = $product->categories()->first();
        $interestId = $firstCategory?->id;

        // activity_id: first store category ID from store's categories
        $store = $product->store;
        $firstStoreCategory = $store ? $store->categories()->first() : null;
        $activityId = $firstStoreCategory?->id;

        return [
            'id' => $product->offer_id,
            'product_id' => $product->id,
            'store_id' => $product->store_id,
            'image_url' => $imageUrl,
            'title' => $title,
            'store_name' => $product->store_name,
            'original_price' => $basePrice,
            'discounted_price' => $discountedPrice,
            'save_percent' => round($discountPercent, 2),
            'interest_id' => $interestId,
            'activity_id' => $activityId,
            'is_favorite' => \in_array($product->id, $favoritedProductIds),
            'created_at' => $product->offer_created_at,
            'saved_count' => (int) $product->favorites_count,
        ];
    }
}
