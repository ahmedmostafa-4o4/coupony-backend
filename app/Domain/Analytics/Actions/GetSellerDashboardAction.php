<?php

namespace App\Domain\Analytics\Actions;

use App\Domain\Analytics\Services\GrowthCalculator;
use App\Domain\Analytics\Services\HeatmapBuilder;
use App\Domain\Analytics\Services\PercentageNormalizer;
use App\Domain\Analytics\Services\PeriodResolver;
use App\Domain\Product\Enums\OfferClaimStatus;
use App\Domain\Product\Enums\ProductOfferStatus;
use App\Domain\Product\Models\OfferClaim;
use App\Domain\Product\Models\ProductOffer;
use App\Domain\Product\Models\ProductView;
use App\Domain\Store\Models\Store;
use App\Domain\Store\Models\StoreFollowers;
use Illuminate\Support\Facades\Cache;

class GetSellerDashboardAction
{
    /**
     * Cache TTL in seconds (15 minutes).
     */
    private const CACHE_TTL = 900;

    /**
     * Execute the seller dashboard action.
     *
     * @param Store $store The seller's store
     * @param string $period The period filter
     * @return array The dashboard data
     */
    public function execute(Store $store, string $period): array
    {
        $cacheKey = "seller_analytics:{$store->id}:{$period}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($store, $period) {
            return $this->computeDashboard($store, $period);
        });
    }

    /**
     * Compute all dashboard metrics.
     */
    private function computeDashboard(Store $store, string $period): array
    {
        [$currentStart, $currentEnd, $previousStart, $previousEnd] = PeriodResolver::resolve($period);

        return [
            'monthly_goal' => $this->computeMonthlyGoal($store),
            'new_followers' => $this->computeNewFollowers($store, $currentStart, $currentEnd, $previousStart, $previousEnd),
            'store_visits' => $this->computeStoreVisits($store, $currentStart, $currentEnd, $previousStart, $previousEnd),
            'offer_distribution' => $this->computeOfferDistribution($store),
            'peak_redemption_times' => $this->computePeakRedemptionTimes($store, $currentStart, $currentEnd),
            'top_performing_offers' => $this->computeTopPerformingOffers($store, $currentStart, $currentEnd),
        ];
    }

    /**
     * Compute monthly goal metrics.
     * Returns goal value, current redemptions this month, and achievement percentage.
     */
    private function computeMonthlyGoal(Store $store): array
    {
        $goal = $store->monthly_goal;

        $currentMonthRedemptions = OfferClaim::where('store_id', $store->id)
            ->where('status', OfferClaimStatus::REDEEMED)
            ->where('redeemed_at', '>=', now()->startOfMonth())
            ->where('redeemed_at', '<=', now())
            ->count();

        $achievementPercent = 0.0;
        if ($goal !== null && $goal > 0) {
            $achievementPercent = round(($currentMonthRedemptions / $goal) * 100, 1);
        }

        return [
            'goal' => $goal,
            'current' => $currentMonthRedemptions,
            'achievement_percent' => $achievementPercent,
        ];
    }

    /**
     * Compute new followers count and growth percentage.
     */
    private function computeNewFollowers(Store $store, $currentStart, $currentEnd, $previousStart, $previousEnd): array
    {
        $currentCount = $this->countFollowersInPeriod($store, $currentStart, $currentEnd);
        $previousCount = $this->countFollowersInPeriod($store, $previousStart, $previousEnd);

        $growthPercent = GrowthCalculator::calculate($currentCount, $previousCount);

        return [
            'count' => $currentCount,
            'growth_percent' => $growthPercent,
        ];
    }

    /**
     * Count followers in a given period.
     */
    private function countFollowersInPeriod(Store $store, $start, $end): int
    {
        $query = StoreFollowers::where('store_id', $store->id);

        if ($start !== null) {
            $query->where('followed_at', '>=', $start);
        }

        if ($end !== null) {
            $query->where('followed_at', '<=', $end);
        }

        return $query->count();
    }

    /**
     * Compute store visits count and growth percentage.
     */
    private function computeStoreVisits(Store $store, $currentStart, $currentEnd, $previousStart, $previousEnd): array
    {
        $currentCount = $this->countVisitsInPeriod($store, $currentStart, $currentEnd);
        $previousCount = $this->countVisitsInPeriod($store, $previousStart, $previousEnd);

        $growthPercent = GrowthCalculator::calculate($currentCount, $previousCount);

        return [
            'count' => $currentCount,
            'growth_percent' => $growthPercent,
        ];
    }

    /**
     * Count product views for the store in a given period.
     */
    private function countVisitsInPeriod(Store $store, $start, $end): int
    {
        $query = ProductView::whereHas('product', function ($q) use ($store) {
            $q->where('store_id', $store->id);
        });

        if ($start !== null) {
            $query->where('created_at', '>=', $start);
        }

        if ($end !== null) {
            $query->where('created_at', '<=', $end);
        }

        return $query->count();
    }

    /**
     * Compute offer distribution by type (normalized percentages).
     */
    private function computeOfferDistribution(Store $store): array
    {
        $offers = ProductOffer::whereHas('product', function ($q) use ($store) {
            $q->where('store_id', $store->id);
        })
            ->where('status', ProductOfferStatus::ACTIVE)
            ->selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->pluck('count', 'type')
            ->toArray();

        if (empty($offers)) {
            return [];
        }

        $types = array_keys($offers);
        $counts = array_values($offers);

        $percentages = PercentageNormalizer::normalize($counts);

        $result = [];
        foreach ($types as $index => $type) {
            $result[] = [
                'type' => $type,
                'percentage' => $percentages[$index],
            ];
        }

        return $result;
    }

    /**
     * Compute peak redemption times heatmap.
     */
    private function computePeakRedemptionTimes(Store $store, $currentStart, $currentEnd): array
    {
        $query = OfferClaim::where('store_id', $store->id)
            ->where('status', OfferClaimStatus::REDEEMED)
            ->whereNotNull('redeemed_at');

        if ($currentStart !== null) {
            $query->where('redeemed_at', '>=', $currentStart);
        }

        if ($currentEnd !== null) {
            $query->where('redeemed_at', '<=', $currentEnd);
        }

        $redemptions = $query->get(['redeemed_at']);

        return HeatmapBuilder::build($redemptions);
    }

    /**
     * Compute top performing offers (top 10 by usage count).
     */
    private function computeTopPerformingOffers(Store $store, $currentStart, $currentEnd): array
    {
        $query = OfferClaim::where('offer_claims.store_id', $store->id)
            ->where('offer_claims.status', OfferClaimStatus::REDEEMED)
            ->whereNotNull('offer_claims.redeemed_at');

        if ($currentStart !== null) {
            $query->where('offer_claims.redeemed_at', '>=', $currentStart);
        }

        if ($currentEnd !== null) {
            $query->where('offer_claims.redeemed_at', '<=', $currentEnd);
        }

        $topOffers = $query
            ->join('product_offers', 'offer_claims.offer_id', '=', 'product_offers.id')
            ->join('products', 'offer_claims.product_id', '=', 'products.id')
            ->selectRaw('products.title as product_title, product_offers.type as offer_type, product_offers.label as offer_label, COUNT(offer_claims.id) as usage_count')
            ->groupBy('offer_claims.offer_id', 'products.title', 'product_offers.type', 'product_offers.label')
            ->orderByDesc('usage_count')
            ->limit(10)
            ->get();

        if ($topOffers->isEmpty()) {
            return [];
        }

        return $topOffers->map(function ($offer) {
            return [
                'product_title' => $offer->product_title,
                'offer_type' => $offer->offer_type,
                'offer_label' => $offer->offer_label,
                'usage_count' => (int) $offer->usage_count,
            ];
        })->toArray();
    }
}
