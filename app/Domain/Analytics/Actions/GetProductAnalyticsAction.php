<?php

namespace App\Domain\Analytics\Actions;

use App\Domain\Analytics\Services\AnalyticsCache;
use App\Domain\Analytics\Services\PercentageNormalizer;
use App\Domain\Analytics\Services\PeriodResolver;
use App\Domain\Product\Models\Product;
use App\Domain\Product\Models\ProductComment;
use App\Domain\Product\Models\ProductFavorite;
use App\Domain\Product\Models\ProductLike;
use App\Domain\Product\Models\ProductShare;
use App\Domain\Product\Models\ProductView;
use App\Domain\Store\Models\StoreFollowers;
use App\Domain\Store\Models\StoreProfileView;
use App\Domain\User\Models\Profile;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class GetProductAnalyticsAction
{
    /**
     * Cache TTL in seconds (1 hour).
     */
    private const CACHE_TTL = 3600;

    /**
     * Traffic source types.
     */
    private const TRAFFIC_SOURCES = ['search', 'explore', 'profile', 'direct', 'recommendation'];

    /**
     * Age group ranges.
     */
    private const AGE_GROUPS = ['13-17', '18-24', '25-34', '35-44', '45-54', '55-64', '65+'];

    /**
     * Execute the product analytics action.
     */
    public function execute(Product $product, string $period, ?string $startDate = null, ?string $endDate = null): array
    {
        $rangeKey = PeriodResolver::cacheKey($period, $startDate, $endDate);
        $cacheKey = AnalyticsCache::productKey($product->id, $rangeKey);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($product, $period, $startDate, $endDate) {
            return $this->computeAnalytics($product, $period, $startDate, $endDate);
        });
    }

    /**
     * Compute all analytics sections for the product.
     */
    private function computeAnalytics(Product $product, string $period, ?string $startDate, ?string $endDate): array
    {
        [$currentStart, $currentEnd, $previousStart, $previousEnd] = PeriodResolver::resolve($period, $startDate, $endDate);

        return [
            'header' => $this->computeHeader($product, $currentStart, $currentEnd),
            'overview' => $this->computeOverview($product, $currentStart, $currentEnd),
            'engagement' => $this->computeEngagement($product, $currentStart, $currentEnd, $period),
            'audience' => $this->computeAudience($product, $currentStart, $currentEnd),
        ];
    }

    /**
     * Compute header metrics: views, likes, comments, saves.
     */
    private function computeHeader(Product $product, ?Carbon $start, ?Carbon $end): array
    {
        return [
            'views' => $this->countInPeriod(ProductView::class, 'product_id', $product->id, $start, $end),
            'likes' => $this->countInPeriod(ProductLike::class, 'product_id', $product->id, $start, $end),
            'comments' => $this->countInPeriod(ProductComment::class, 'product_id', $product->id, $start, $end),
            'saves' => $this->countInPeriod(ProductFavorite::class, 'product_id', $product->id, $start, $end),
        ];
    }

    /**
     * Compute overview: impressions, reached_accounts, profile_visits, new_followers, traffic_sources.
     */
    private function computeOverview(Product $product, ?Carbon $start, ?Carbon $end): array
    {
        $impressions = $this->countInPeriod(ProductView::class, 'product_id', $product->id, $start, $end);
        $reachedAccounts = $this->countDistinctInPeriod(ProductView::class, 'product_id', $product->id, 'user_id', $start, $end);

        // Profile visits: actual store profile page views in the period
        $storeId = $product->store_id;
        $profileVisits = $this->countStoreProfileViewsInPeriod($storeId, $start, $end);

        // New followers for the product's store in the period
        $newFollowers = $this->countStoreFollowersInPeriod($storeId, $start, $end);

        // Traffic sources - real data from product_views.source column
        $trafficSources = $this->getTrafficSources($product, $start, $end);

        return [
            'impressions' => $impressions,
            'reached_accounts' => $reachedAccounts,
            'profile_visits' => $profileVisits,
            'new_followers' => $newFollowers,
            'traffic_sources' => $trafficSources,
        ];
    }

    /**
     * Compute engagement: total_interactions, engagement_rate, trend, action_breakdown.
     */
    private function computeEngagement(Product $product, ?Carbon $start, ?Carbon $end, string $period): array
    {
        $likes = $this->countInPeriod(ProductLike::class, 'product_id', $product->id, $start, $end);
        $comments = $this->countInPeriod(ProductComment::class, 'product_id', $product->id, $start, $end);
        $saves = $this->countInPeriod(ProductFavorite::class, 'product_id', $product->id, $start, $end);
        $shares = $this->countInPeriod(ProductShare::class, 'product_id', $product->id, $start, $end);

        $totalInteractions = $likes + $comments + $saves + $shares;
        $impressions = $this->countInPeriod(ProductView::class, 'product_id', $product->id, $start, $end);

        $engagementRate = $impressions > 0
            ? round(($totalInteractions / $impressions) * 100, 2)
            : 0.0;

        $trend = $this->computeTrend($product, $start, $end, $period);

        return [
            'total_interactions' => $totalInteractions,
            'engagement_rate' => $engagementRate,
            'trend' => $trend,
            'trends' => $this->computeTrendPresets($product, $start, $end),
            'action_breakdown' => [
                'likes' => $likes,
                'comments' => $comments,
                'saves' => $saves,
                'shares' => $shares,
            ],
        ];
    }

    private function computeTrendPresets(Product $product, ?Carbon $start, ?Carbon $end): array
    {
        return [
            'days' => $this->computeDailyTrendPreset($product),
            'months' => $this->computeMonthlyTrendPreset($product),
            'peak_times' => $this->computePeakTimeTrendPreset($product, $start, $end),
        ];
    }

    private function computeDailyTrendPreset(Product $product): array
    {
        $start = Carbon::now()->subDays(6)->startOfDay();
        $buckets = [];

        for ($index = 0; $index < 7; $index++) {
            $bucketStart = $start->copy()->addDays($index);
            $bucketEnd = $bucketStart->copy()->endOfDay();

            $buckets[] = [
                'index' => $index,
                'date' => $bucketStart->format('Y-m-d'),
                'count' => $this->countInteractionsBetween($product, $bucketStart, $bucketEnd),
            ];
        }

        return $buckets;
    }

    private function computeMonthlyTrendPreset(Product $product): array
    {
        $start = Carbon::now()->subMonthsNoOverflow(5)->startOfMonth();
        $buckets = [];

        for ($index = 0; $index < 6; $index++) {
            $bucketStart = $start->copy()->addMonthsNoOverflow($index)->startOfMonth();
            $bucketEnd = $bucketStart->copy()->endOfMonth();

            $buckets[] = [
                'index' => $index,
                'month' => $bucketStart->format('Y-m'),
                'count' => $this->countInteractionsBetween($product, $bucketStart, $bucketEnd),
            ];
        }

        return $buckets;
    }

    private function computePeakTimeTrendPreset(Product $product, ?Carbon $start, ?Carbon $end): array
    {
        $effectiveStart = ($start ?? $product->created_at ?? Carbon::now())->copy()->startOfDay();
        $effectiveEnd = ($end ?? Carbon::now())->copy()->endOfDay();
        $timestamps = $this->interactionTimestampsBetween($product, $effectiveStart, $effectiveEnd);

        $buckets = [
            ['label' => 'night', 'start_hour' => 0, 'end_hour' => 6],
            ['label' => 'morning', 'start_hour' => 6, 'end_hour' => 12],
            ['label' => 'afternoon', 'start_hour' => 12, 'end_hour' => 18],
            ['label' => 'evening', 'start_hour' => 18, 'end_hour' => 24],
        ];

        return array_map(function (array $bucket, int $index) use ($timestamps): array {
            $count = $timestamps->filter(function (Carbon $timestamp) use ($bucket): bool {
                $hour = $timestamp->hour;

                return $hour >= $bucket['start_hour'] && $hour < $bucket['end_hour'];
            })->count();

            return [
                'index' => $index,
                'label' => $bucket['label'],
                'start_hour' => $bucket['start_hour'],
                'end_hour' => $bucket['end_hour'],
                'count' => $count,
            ];
        }, $buckets, array_keys($buckets));
    }

    private function countInteractionsBetween(Product $product, Carbon $start, Carbon $end): int
    {
        return array_sum(array_map(
            fn (string $model): int => $this->countInPeriod($model, 'product_id', $product->id, $start, $end),
            [ProductLike::class, ProductComment::class, ProductFavorite::class, ProductShare::class],
        ));
    }

    private function interactionTimestampsBetween(Product $product, Carbon $start, Carbon $end)
    {
        return collect([ProductLike::class, ProductComment::class, ProductFavorite::class, ProductShare::class])
            ->flatMap(fn (string $model) => $model::query()
                ->where('product_id', $product->id)
                ->where('created_at', '>=', $start)
                ->where('created_at', '<=', $end)
                ->pluck('created_at')
                ->map(fn ($timestamp) => Carbon::parse($timestamp)));
    }

    /**
     * Compute audience: followers_percent, non_followers_percent, age_groups, gender_groups.
     */
    private function computeAudience(Product $product, ?Carbon $start, ?Carbon $end): array
    {
        $storeId = $product->store_id;

        // Get unique users who interacted with this product
        $interactingUserIds = $this->getInteractingUserIds($product, $start, $end);
        $totalInteractors = count($interactingUserIds);

        if ($totalInteractors === 0) {
            // Zero-data defaults
            return [
                'followers_percent' => 50.0,
                'non_followers_percent' => 50.0,
                'age_groups' => $this->getDefaultAgeGroups(),
                'gender_groups' => [],
            ];
        }

        // Calculate follower/non-follower split
        $followerCount = StoreFollowers::where('store_id', $storeId)
            ->whereIn('user_id', $interactingUserIds)
            ->count();

        $nonFollowerCount = $totalInteractors - $followerCount;

        $followerSplit = PercentageNormalizer::normalize([$followerCount, $nonFollowerCount]);

        $followersPercent = $followerSplit[0] ?? 50.0;
        $nonFollowersPercent = $followerSplit[1] ?? 50.0;

        // Age groups from profiles.date_of_birth
        $ageGroups = $this->computeAgeGroups($interactingUserIds);

        // Gender groups from profiles.gender
        $genderGroups = $this->computeGenderGroups($interactingUserIds);

        return [
            'followers_percent' => $followersPercent,
            'non_followers_percent' => $nonFollowersPercent,
            'age_groups' => $ageGroups,
            'gender_groups' => $genderGroups,
        ];
    }

    /**
     * Compute age group distribution from user profiles.
     * Uses profiles.date_of_birth to calculate ages and bucket them.
     * Falls back to default equal distribution if no DOB data exists.
     */
    private function computeAgeGroups(array $userIds): array
    {
        $profiles = Profile::whereIn('user_id', $userIds)
            ->whereNotNull('date_of_birth')
            ->pluck('date_of_birth');

        if ($profiles->isEmpty()) {
            return $this->getDefaultAgeGroups();
        }

        // Bucket ages into groups
        $buckets = array_fill_keys(self::AGE_GROUPS, 0);

        foreach ($profiles as $dob) {
            $age = Carbon::parse($dob)->age;
            $bucket = $this->getAgeBucket($age);
            if ($bucket !== null) {
                $buckets[$bucket]++;
            }
        }

        $counts = array_values($buckets);

        // If all buckets are zero (shouldn't happen but safety check)
        if (array_sum($counts) === 0) {
            return $this->getDefaultAgeGroups();
        }

        $normalized = PercentageNormalizer::normalize($counts);

        $ageGroups = [];
        foreach (self::AGE_GROUPS as $index => $range) {
            $ageGroups[] = [
                'range' => $range,
                'percentage' => $normalized[$index],
            ];
        }

        return $ageGroups;
    }

    /**
     * Map an age to its bucket range.
     */
    private function getAgeBucket(int $age): ?string
    {
        if ($age >= 13 && $age <= 17) {
            return '13-17';
        }
        if ($age >= 18 && $age <= 24) {
            return '18-24';
        }
        if ($age >= 25 && $age <= 34) {
            return '25-34';
        }
        if ($age >= 35 && $age <= 44) {
            return '35-44';
        }
        if ($age >= 45 && $age <= 54) {
            return '45-54';
        }
        if ($age >= 55 && $age <= 64) {
            return '55-64';
        }
        if ($age >= 65) {
            return '65+';
        }

        return null; // Under 13
    }

    /**
     * Compute gender distribution from user profiles.
     * Uses profiles.gender column.
     * Returns empty array if no gender data exists.
     */
    private function computeGenderGroups(array $userIds): array
    {
        $genderCounts = Profile::whereIn('user_id', $userIds)
            ->whereNotNull('gender')
            ->selectRaw('gender, COUNT(*) as count')
            ->groupBy('gender')
            ->pluck('count', 'gender')
            ->toArray();

        if (empty($genderCounts)) {
            return [];
        }

        $genders = array_keys($genderCounts);
        $counts = array_values($genderCounts);

        $normalized = PercentageNormalizer::normalize($counts);

        $genderGroups = [];
        foreach ($genders as $index => $gender) {
            $genderGroups[] = [
                'gender' => $gender,
                'percentage' => $normalized[$index],
            ];
        }

        return $genderGroups;
    }

    /**
     * Count records in a period for a given model.
     */
    private function countInPeriod(string $model, string $foreignKey, string $foreignValue, ?Carbon $start, ?Carbon $end): int
    {
        $query = $model::where($foreignKey, $foreignValue);

        if ($start !== null) {
            $query->where('created_at', '>=', $start);
        }

        if ($end !== null) {
            $query->where('created_at', '<=', $end);
        }

        return $query->count();
    }

    /**
     * Count distinct values in a period for a given model.
     */
    private function countDistinctInPeriod(string $model, string $foreignKey, string $foreignValue, string $distinctColumn, ?Carbon $start, ?Carbon $end): int
    {
        $query = $model::where($foreignKey, $foreignValue);

        if ($start !== null) {
            $query->where('created_at', '>=', $start);
        }

        if ($end !== null) {
            $query->where('created_at', '<=', $end);
        }

        return $query->distinct($distinctColumn)->count($distinctColumn);
    }

    /**
     * Count store followers gained in a period.
     */
    private function countStoreFollowersInPeriod(string $storeId, ?Carbon $start, ?Carbon $end): int
    {
        $query = StoreFollowers::where('store_id', $storeId);

        if ($start !== null) {
            $query->where('followed_at', '>=', $start);
        }

        if ($end !== null) {
            $query->where('followed_at', '<=', $end);
        }

        return $query->count();
    }

    /**
     * Count store profile views in a period.
     */
    private function countStoreProfileViewsInPeriod(string $storeId, ?Carbon $start, ?Carbon $end): int
    {
        $query = StoreProfileView::where('store_id', $storeId);

        if ($start !== null) {
            $query->where('created_at', '>=', $start);
        }

        if ($end !== null) {
            $query->where('created_at', '<=', $end);
        }

        return $query->count();
    }

    /**
     * Get traffic sources from real product view data.
     * Queries the `source` column on product_views, groups by source, and normalizes.
     * Returns empty array when no views with source data exist.
     */
    private function getTrafficSources(Product $product, ?Carbon $start, ?Carbon $end): array
    {
        $query = ProductView::where('product_id', $product->id)
            ->whereNotNull('source');

        if ($start !== null) {
            $query->where('created_at', '>=', $start);
        }

        if ($end !== null) {
            $query->where('created_at', '<=', $end);
        }

        $sourceCounts = $query->selectRaw('source, COUNT(*) as count')
            ->groupBy('source')
            ->pluck('count', 'source')
            ->toArray();

        if (empty($sourceCounts)) {
            return [];
        }

        // Ensure consistent ordering by the defined source types
        $orderedCounts = [];
        $orderedSources = [];
        foreach (self::TRAFFIC_SOURCES as $source) {
            if (isset($sourceCounts[$source])) {
                $orderedSources[] = $source;
                $orderedCounts[] = $sourceCounts[$source];
            }
        }

        if (empty($orderedCounts)) {
            return [];
        }

        $normalized = PercentageNormalizer::normalize($orderedCounts);

        $sources = [];
        foreach ($orderedSources as $index => $source) {
            $sources[] = [
                'source' => $source,
                'percentage' => $normalized[$index],
            ];
        }

        return $sources;
    }

    /**
     * Compute engagement trend series.
     * Daily grouping for periods ≤30 days, monthly for >30 days.
     */
    private function computeTrend(Product $product, ?Carbon $start, ?Carbon $end, string $period): array
    {
        // Determine if we should group by day or month
        $groupByMonth = $this->shouldGroupByMonth($start, $end, $period);

        // For 'all' period with no start, use product creation date
        $effectiveStart = $start ?? $product->created_at;
        $effectiveEnd = $end ?? Carbon::now();

        if ($effectiveStart === null) {
            return [];
        }

        // Query interactions (likes + comments + saves) grouped by date
        $carbonFormat = $groupByMonth ? 'Y-m' : 'Y-m-d';
        $dateExpression = $this->getDateGroupExpression($groupByMonth);

        // Combine interactions from multiple tables
        $likesQuery = ProductLike::where('product_id', $product->id)
            ->where('created_at', '>=', $effectiveStart)
            ->where('created_at', '<=', $effectiveEnd)
            ->selectRaw("{$dateExpression} as date_key, COUNT(*) as cnt")
            ->groupBy('date_key');

        $commentsQuery = ProductComment::where('product_id', $product->id)
            ->where('created_at', '>=', $effectiveStart)
            ->where('created_at', '<=', $effectiveEnd)
            ->selectRaw("{$dateExpression} as date_key, COUNT(*) as cnt")
            ->groupBy('date_key');

        $savesQuery = ProductFavorite::where('product_id', $product->id)
            ->where('created_at', '>=', $effectiveStart)
            ->where('created_at', '<=', $effectiveEnd)
            ->selectRaw("{$dateExpression} as date_key, COUNT(*) as cnt")
            ->groupBy('date_key');

        $sharesQuery = ProductShare::where('product_id', $product->id)
            ->where('created_at', '>=', $effectiveStart)
            ->where('created_at', '<=', $effectiveEnd)
            ->selectRaw("{$dateExpression} as date_key, COUNT(*) as cnt")
            ->groupBy('date_key');

        // Merge results
        $interactionsByDate = collect();

        foreach ([$likesQuery, $commentsQuery, $savesQuery, $sharesQuery] as $query) {
            $results = $query->get();
            foreach ($results as $row) {
                $current = $interactionsByDate->get($row->date_key, 0);
                $interactionsByDate->put($row->date_key, $current + $row->cnt);
            }
        }

        // Build complete date series
        $trend = [];
        $current = $effectiveStart->copy();

        while ($current->lte($effectiveEnd)) {
            $key = $current->format($carbonFormat);
            $trend[] = [
                'date' => $key,
                'count' => $interactionsByDate->get($key, 0),
            ];

            if ($groupByMonth) {
                $current->addMonth()->startOfMonth();
            } else {
                $current->addDay();
            }
        }

        return $trend;
    }

    /**
     * Determine if trend should be grouped by month (period > 30 days).
     */
    private function shouldGroupByMonth(?Carbon $start, ?Carbon $end, string $period): bool
    {
        // 'this_year' and 'all' are always > 30 days
        if (in_array($period, ['this_year', 'all'])) {
            return true;
        }

        if ($start === null || $end === null) {
            return true;
        }

        return $start->diffInDays($end) > 30;
    }

    /**
     * Get a database-agnostic date grouping expression.
     * Uses strftime for SQLite and DATE_FORMAT for MySQL.
     */
    private function getDateGroupExpression(bool $groupByMonth): string
    {
        $driver = config('database.default');
        $connection = config("database.connections.{$driver}.driver", $driver);

        if ($connection === 'sqlite') {
            $format = $groupByMonth ? '%Y-%m' : '%Y-%m-%d';

            return "strftime('{$format}', created_at)";
        }

        $format = $groupByMonth ? '%Y-%m' : '%Y-%m-%d';

        return "DATE_FORMAT(created_at, '{$format}')";
    }

    /**
     * Get unique user IDs who interacted with the product in the period.
     */
    private function getInteractingUserIds(Product $product, ?Carbon $start, ?Carbon $end): array
    {
        $userIds = collect();

        // Collect user IDs from views
        $viewUsers = $this->getUserIdsFromModel(ProductView::class, $product->id, $start, $end);
        $userIds = $userIds->merge($viewUsers);

        // Collect user IDs from likes
        $likeUsers = $this->getUserIdsFromModel(ProductLike::class, $product->id, $start, $end);
        $userIds = $userIds->merge($likeUsers);

        // Collect user IDs from comments
        $commentUsers = $this->getUserIdsFromModel(ProductComment::class, $product->id, $start, $end);
        $userIds = $userIds->merge($commentUsers);

        // Collect user IDs from favorites
        $favoriteUsers = $this->getUserIdsFromModel(ProductFavorite::class, $product->id, $start, $end);
        $userIds = $userIds->merge($favoriteUsers);

        return $userIds->unique()->filter()->values()->toArray();
    }

    /**
     * Get distinct user IDs from a model for a product in a period.
     */
    private function getUserIdsFromModel(string $model, string $productId, ?Carbon $start, ?Carbon $end): array
    {
        $query = $model::where('product_id', $productId)->whereNotNull('user_id');

        if ($start !== null) {
            $query->where('created_at', '>=', $start);
        }

        if ($end !== null) {
            $query->where('created_at', '<=', $end);
        }

        return $query->distinct()->pluck('user_id')->toArray();
    }

    /**
     * Get default age groups with equal distribution (100/7 ≈ 14.3% each, normalized to sum to 100.0).
     */
    private function getDefaultAgeGroups(): array
    {
        $rawValues = array_fill(0, count(self::AGE_GROUPS), 1);
        $normalized = PercentageNormalizer::normalize($rawValues);

        $ageGroups = [];
        foreach (self::AGE_GROUPS as $index => $range) {
            $ageGroups[] = [
                'range' => $range,
                'percentage' => $normalized[$index],
            ];
        }

        return $ageGroups;
    }
}
