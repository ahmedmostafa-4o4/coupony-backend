<?php

namespace App\Domain\Product\Services;

use App\Domain\Product\Repositories\ProductRepository;
use App\Domain\User\Models\User;
use Illuminate\Support\Collection;

class ProductRecommendationService
{
    public function __construct(
        private readonly ProductRepository $products,
        private readonly RecommendationMlService $ml,
    ) {
    }

    public function recommendFor(User $user, int $limit): Collection
    {
        $preferences = $user->preferences;

        if (($preferences?->enable_personalized_recommendations ?? true) === false) {
            return $this->products->popularPublicProducts($limit, $user);
        }

        $seedProductIds = $this->products->recentInteractionProductIds(
            $user,
            max($limit, (int) config('services.recommendation.seed_limit', 20)),
            ($preferences?->browsing_history_tracking ?? true)
        );

        if ($seedProductIds === [] || ! (bool) config('services.recommendation.enabled', true)) {
            return $this->products->popularPublicProducts($limit, $user, $seedProductIds);
        }

        $recommendedIds = $this->ml->recommend($user->id, $seedProductIds, $limit);

        if ($recommendedIds === null || $recommendedIds === []) {
            return $this->products->popularPublicProducts($limit, $user, $seedProductIds);
        }

        $recommendedProducts = $this->products->publicProductsByIdsInOrder($recommendedIds, $user, $seedProductIds);

        if ($recommendedProducts->count() >= $limit) {
            return $recommendedProducts->take($limit)->values();
        }

        $fallbackProducts = $this->products->popularPublicProducts(
            $limit - $recommendedProducts->count(),
            $user,
            [...$seedProductIds, ...$recommendedProducts->pluck('id')->all()]
        );

        return $recommendedProducts
            ->concat($fallbackProducts)
            ->take($limit)
            ->values();
    }
}
