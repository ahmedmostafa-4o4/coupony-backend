<?php

namespace App\Domain\Search\Services;

use App\Domain\Product\Models\Product;
use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class SearchOfferService
{
    public function search(array $filters, ?User $user): array
    {
        $query = Product::query()
            ->active()
            ->with(['store.addresses', 'images']);

        $query = $this->applySearchQuery($query, $filters['q'] ?? '');
        $query = $this->applyCategory($query, $filters['category'] ?? null);
        $query = $this->applyFilters($query, $filters);
        
        $query = $this->applySortingAndLocation($query, $filters);

        // Optional Auth: Favorites
        if ($user) {
            $query->withExists(['favorites as is_favorited' => function ($q) use ($user) {
                $q->where('user_id', $user->id);
            }]);
        }

        $page = (int) ($filters['page'] ?? 1);
        $pageSize = (int) ($filters['page_size'] ?? 20);

        $paginator = $query->paginate($pageSize, ['*'], 'page', $page);

        // Simple Facets computation (can be expanded)
        // Note: For large datasets, facets should be calculated on a cloned query without pagination
        $facetsQuery = clone $query;
        $priceStats = $facetsQuery->getQuery()->getConnection()
            ->query()
            ->fromSub($facetsQuery, 'filtered_products')
            ->selectRaw('MIN(base_price) as min_price, MAX(base_price) as max_price')
            ->first();

        $total = $paginator->total();
        $totalPages = (int) ceil($total / $pageSize);
        $hasMore = $page < $totalPages;

        return [
            'success' => true,
            'message' => 'Search results loaded',
            'data' => [
                'query' => $filters['q'] ?? '',
                'items' => $paginator->items(), // Will be transformed in Controller
                'pagination' => [
                    'page' => $page,
                    'page_size' => $pageSize,
                    'total' => $total,
                    'total_pages' => $totalPages,
                    'has_more' => $hasMore,
                ],
                'facets' => [
                    'categories' => [
                        ['id' => 'shoes', 'label_key' => 'search_category_shoes', 'count' => 0], // Mocks counts for now
                        ['id' => 'watches', 'label_key' => 'search_category_watches', 'count' => 0],
                        ['id' => 'accessories', 'label_key' => 'search_category_accessories', 'count' => 0],
                    ],
                    'price' => [
                        'min' => $priceStats ? (float) $priceStats->min_price : 0,
                        'max' => $priceStats ? (float) $priceStats->max_price : 0,
                    ]
                ]
            ]
        ];
    }

    private function applySearchQuery(Builder $query, string $q): Builder
    {
        if (empty(trim($q))) {
            return $query;
        }

        $normalizedQ = $this->normalizeArabicText($q);

        return $query->where(function (Builder $sub) use ($q, $normalizedQ) {
            $sub->where('products.title', 'LIKE', "%{$q}%")
                ->orWhere('products.title', 'LIKE', "%{$normalizedQ}%")
                ->orWhere('products.description', 'LIKE', "%{$q}%")
                ->orWhereHas('store', function ($storeSub) use ($q) {
                    $storeSub->where('stores.name', 'LIKE', "%{$q}%");
                })
                ->orWhereHas('categories', function ($catSub) use ($q) {
                    $catSub->where('categories.name', 'LIKE', "%{$q}%")
                           ->orWhere('categories.name_ar', 'LIKE', "%{$q}%")
                           ->orWhere('categories.name_en', 'LIKE', "%{$q}%");
                });
        });
    }

    private function normalizeArabicText(string $text): string
    {
        // Simple normalization: replace variations of Alef with plain Alef
        $text = preg_replace('/[أإآ]/u', 'ا', $text);
        // Remove tashkeel
        $text = preg_replace('/[\x{064B}-\x{065F}]/u', '', $text);
        return $text;
    }

    private function applyCategory(Builder $query, $categoryId): Builder
    {
        if (!$categoryId) {
            return $query;
        }

        return $query->whereHas('categories', function ($catSub) use ($categoryId) {
            $catSub->where('categories.id', $categoryId);
        });
    }

    private function applyFilters(Builder $query, array $filters): Builder
    {
        if (isset($filters['min_rating'])) {
            $query->where('products.rating_avg', '>=', $filters['min_rating']);
        }

        if (isset($filters['min_price'])) {
            $query->where('products.base_price', '>=', $filters['min_price']);
        }

        if (isset($filters['max_price'])) {
            $query->where('products.base_price', '<=', $filters['max_price']);
        }

        return $query;
    }

    private function applySortingAndLocation(Builder $query, array $filters): Builder
    {
        $quickFilter = $filters['quick_filter'] ?? 'all';
        $sortBy = $filters['sort_by'] ?? 'popular';

        if ($quickFilter === 'nearby' && isset($filters['lat']) && isset($filters['lng'])) {
            $lat = (float) $filters['lat'];
            $lng = (float) $filters['lng'];

            // Haversine formula joined on stores -> addressables -> addresses
            $query->select('products.*')
                ->join('stores', 'products.store_id', '=', 'stores.id')
                ->join('addressables', function ($join) {
                    $join->on('stores.id', '=', 'addressables.owner_id')
                         ->where('addressables.owner_type', \App\Domain\Store\Models\Store::class);
                })
                ->join('addresses', 'addressables.address_id', '=', 'addresses.id')
                ->selectRaw(
                    '( 6371 * acos( cos( radians(?) ) * cos( radians( addresses.latitude ) ) * cos( radians( addresses.longitude ) - radians(?) ) + sin( radians(?) ) * sin( radians( addresses.latitude ) ) ) ) AS distance',
                    [$lat, $lng, $lat]
                )
                ->orderBy('distance', 'asc');
            return $query;
        }

        if ($quickFilter === 'newest') {
            return $query->latest('products.created_at');
        }

        // Standard sorting if not nearby/newest quick filter
        match ($sortBy) {
            'popular' => $query->orderByDesc('products.rating_avg')->orderByDesc('products.favorites_count'),
            'newest' => $query->latest('products.created_at'),
            'price_high' => $query->orderByDesc('products.base_price'),
            'price_low' => $query->orderBy('products.base_price'),
            default => $query->latest('products.created_at'),
        };

        return $query;
    }
}
