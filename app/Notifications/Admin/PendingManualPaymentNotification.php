<?php

namespace App\Notifications\Admin;

use App\Domain\Subscription\Models\PaymentSession;

class PendingManualPaymentNotification extends AdminNotification
{
    public function __construct(public PaymentSession $paymentSession) {}

    public function toDatabase($notifiable): array
    {
        return [
            'title' => 'Pending Manual Payment',
            'message' => "A manual payment for store '{$this->paymentSession->store->name}' requires admin approval.",
            'reference_type' => PaymentSession::class,
            'reference_id' => $this->paymentSession->id,
            'data' => [
                'session_id' => $this->paymentSession->id,
                'store_id' => $this->paymentSession->store_id,
                'amount' => $this->paymentSession->amount,
                'currency' => $this->paymentSession->currency,
            ]
        ];
    }
}
