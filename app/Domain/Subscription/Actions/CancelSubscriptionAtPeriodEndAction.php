<?php

namespace App\Domain\Subscription\Actions;

use App\Domain\Subscription\Models\Subscription;
use Illuminate\Validation\ValidationException;

class CancelSubscriptionAtPeriodEndAction
{
    /**
     * @throws ValidationException
     */
    public function execute(Subscription $subscription): Subscription
    {
        if ($subscription->cancelled_at !== null) {
            throw ValidationException::withMessages([
                'subscription' => __('api.subscription.already_cancelled'),
            ]);
        }

        if (! $subscription->status->canCancelAtPeriodEnd()) {
            throw ValidationException::withMessages([
                'subscription' => __('api.subscription.cannot_cancel'),
            ]);
        }

        $subscription->update([
            'cancelled_at' => now(),
        ]);

        return $subscription->fresh();
    }
}
