<?php

namespace App\Domain\Subscription\Notifications;

use App\Domain\Subscription\Models\Subscription;

class SubscriptionExpiringSoonNotification extends BaseSubscriptionNotification
{
    public function __construct(
        private readonly Subscription $subscription,
    ) {}

    public function type(): string
    {
        return 'subscription_expiring_soon';
    }

    public function title(): string
    {
        return 'Subscription expiring soon';
    }

    public function message(): string
    {
        $daysRemaining = now()->diffInDays($this->subscription->current_period_end, false);
        $daysText = $daysRemaining <= 1 ? '1 day' : "{$daysRemaining} days";

        return "Your subscription expires in {$daysText}. Renew now to avoid service interruption.";
    }

    public function data(): array
    {
        return [
            'store_id' => $this->subscription->store_id,
            'subscription_id' => $this->subscription->id,
            'plan_id' => $this->subscription->plan_id,
            'expires_at' => $this->subscription->current_period_end?->toIso8601String(),
        ];
    }

    public function referenceId(): string
    {
        return $this->subscription->id;
    }
}
