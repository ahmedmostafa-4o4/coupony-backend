<?php

namespace App\Domain\Subscription\Repositories;

use App\Domain\Subscription\Models\SubscriptionPlan;
use Illuminate\Support\Collection;

class SubscriptionPlanRepository
{
    /**
     * Get all active plans ordered by sort_order.
     */
    public function findActive(): Collection
    {
        return SubscriptionPlan::where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Find a plan by its ID.
     */
    public function findById(string $id): ?SubscriptionPlan
    {
        return SubscriptionPlan::find($id);
    }
}
