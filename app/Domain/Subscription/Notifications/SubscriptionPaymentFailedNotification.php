<?php

namespace App\Domain\Subscription\Notifications;

use App\Domain\Subscription\Models\PaymentSession;

class SubscriptionPaymentFailedNotification extends BaseSubscriptionNotification
{
    public function __construct(
        private readonly PaymentSession $session,
        private readonly ?string $reason = null,
    ) {}

    public function type(): string
    {
        return 'subscription_payment_failed';
    }

    public function title(): string
    {
        return 'Payment failed';
    }

    public function message(): string
    {
        return 'Your subscription payment has failed. Please try again.';
    }

    public function data(): array
    {
        return [
            'store_id' => $this->session->store_id,
            'session_id' => $this->session->id,
            'plan_id' => $this->session->plan_id,
            'reason' => $this->reason,
        ];
    }

    public function referenceId(): string
    {
        return $this->session->id;
    }
}
