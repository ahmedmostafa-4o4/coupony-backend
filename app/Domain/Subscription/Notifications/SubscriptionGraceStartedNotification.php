<?php

namespace App\Domain\Subscription\Notifications;

use App\Domain\Subscription\Models\Subscription;

class SubscriptionGraceStartedNotification extends BaseSubscriptionNotification
{
    public function __construct(
        private readonly Subscription $subscription,
    ) {}

    public function type(): string
    {
        return 'subscription_grace_started';
    }

    public function title(): string
    {
        return 'Subscription grace period';
    }

    public function message(): string
    {
        return 'Your subscription has expired. You have a grace period to renew before features are restricted.';
    }

    public function data(): array
    {
        return [
            'store_id' => $this->subscription->store_id,
            'subscription_id' => $this->subscription->id,
            'plan_id' => $this->subscription->plan_id,
            'grace_period_end' => $this->subscription->grace_period_end?->toIso8601String(),
        ];
    }

    public function referenceId(): string
    {
        return $this->subscription->id;
    }
}
