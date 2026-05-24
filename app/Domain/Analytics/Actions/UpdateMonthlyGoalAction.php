<?php

namespace App\Domain\Analytics\Actions;

use App\Domain\Store\Models\Store;
use Illuminate\Support\Facades\Cache;

class UpdateMonthlyGoalAction
{
    /**
     * The valid period variants used for cache keys.
     */
    private const PERIODS = ['all', 'today', 'last_7_days', 'this_month', 'this_year'];

    /**
     * Update the monthly goal for a store and invalidate cached dashboard entries.
     */
    public function execute(Store $store, int $goal): int
    {
        $store->update(['monthly_goal' => $goal]);

        $this->invalidateCache($store);

        return $goal;
    }

    /**
     * Invalidate all cached dashboard entries for the store (all period variants).
     */
    private function invalidateCache(Store $store): void
    {
        foreach (self::PERIODS as $period) {
            Cache::forget("seller_analytics:{$store->id}:{$period}");
        }
    }
}
