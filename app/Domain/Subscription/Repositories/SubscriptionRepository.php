<?php

namespace App\Domain\Subscription\Repositories;

use App\Domain\Subscription\Enums\SubscriptionStatus;
use App\Domain\Subscription\Models\Subscription;

class SubscriptionRepository
{
    /**
     * Find the subscription for a store.
     */
    public function findByStore(string $storeId): ?Subscription
    {
        return Subscription::where('store_id', $storeId)->first();
    }

    /**
     * Update subscription status.
     */
    public function updateStatus(Subscription $subscription, SubscriptionStatus $status): Subscription
    {
        $subscription->update(['status' => $status]);

        return $subscription->fresh();
    }
}
