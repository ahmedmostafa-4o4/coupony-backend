<?php

namespace App\Notifications\Admin;

use App\Domain\Subscription\Models\Subscription;

class SubscriptionCancelledNotification extends AdminNotification
{
    public function __construct(public Subscription $subscription) {}

    public function toDatabase($notifiable): array
    {
        return [
            'title' => 'Subscription Cancelled',
            'message' => "The subscription for store '{$this->subscription->store->name}' has been cancelled.",
            'reference_type' => Subscription::class,
            'reference_id' => $this->subscription->id,
            'data' => [
                'subscription_id' => $this->subscription->id,
                'store_id' => $this->subscription->store_id,
            ]
        ];
    }
}
