<?php

namespace App\Domain\Subscription\Notifications;

use App\Domain\Subscription\Models\Subscription;

class SubscriptionDegradedNotification extends BaseSubscriptionNotification
{
    public function __construct(
        private readonly Subscription $subscription,
    ) {}

    public function type(): string
    {
        return 'subscription_degraded';
    }

    public function title(): string
    {
        return 'Subscription degraded';
    }

    public function message(): string
    {
        return 'Your subscription grace period has ended. Some features are now restricted.';
    }

    public function data(): array
    {
        return [
            'store_id' => $this->subscription->store_id,
            'subscription_id' => $this->subscription->id,
            'plan_id' => $this->subscription->plan_id,
            'degraded_period_end' => $this->subscription->degraded_period_end?->toIso8601String(),
        ];
    }

    public function referenceId(): string
    {
        return $this->subscription->id;
    }
}
