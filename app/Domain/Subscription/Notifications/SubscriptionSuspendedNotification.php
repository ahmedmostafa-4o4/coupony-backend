<?php

namespace App\Domain\Subscription\Notifications;

use App\Domain\Subscription\Models\Subscription;

class SubscriptionSuspendedNotification extends BaseSubscriptionNotification
{
    public function __construct(
        private readonly Subscription $subscription,
    ) {}

    public function type(): string
    {
        return 'subscription_suspended';
    }

    public function title(): string
    {
        return 'Subscription suspended';
    }

    public function message(): string
    {
        return 'Your subscription has been suspended. Please renew to restore access.';
    }

    public function data(): array
    {
        return [
            'store_id' => $this->subscription->store_id,
            'subscription_id' => $this->subscription->id,
            'plan_id' => $this->subscription->plan_id,
        ];
    }

    public function referenceId(): string
    {
        return $this->subscription->id;
    }
}
