<?php

namespace App\Domain\Analytics\Actions;

use App\Domain\Analytics\Services\AnalyticsCache;
use App\Domain\Store\Models\Store;

class UpdateMonthlyGoalAction
{
    /**
     * Update the monthly goal for a store and invalidate cached dashboard entries.
     */
    public function execute(Store $store, int $goal): int
    {
        $store->update(['monthly_goal' => $goal]);

        AnalyticsCache::invalidateSeller($store->id);

        return $goal;
    }
}
