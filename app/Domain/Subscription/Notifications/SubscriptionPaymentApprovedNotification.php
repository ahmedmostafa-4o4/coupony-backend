<?php

namespace App\Domain\Subscription\Notifications;

use App\Domain\Subscription\Models\PaymentSession;
use App\Domain\Subscription\Models\Subscription;

class SubscriptionPaymentApprovedNotification extends BaseSubscriptionNotification
{
    public function __construct(
        private readonly Subscription $subscription,
        private readonly PaymentSession $session,
    ) {}

    public function type(): string
    {
        return 'subscription_payment_approved';
    }

    public function title(): string
    {
        return 'Payment approved';
    }

    public function message(): string
    {
        return 'Your subscription payment has been approved and your subscription is now active.';
    }

    public function data(): array
    {
        return [
            'store_id' => $this->subscription->store_id,
            'subscription_id' => $this->subscription->id,
            'plan_id' => $this->subscription->plan_id,
            'session_id' => $this->session->id,
        ];
    }

    public function referenceId(): string
    {
        return $this->subscription->id;
    }
}
