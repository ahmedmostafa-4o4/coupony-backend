<?php

namespace App\Domain\Subscription\Events;

use App\Domain\Subscription\Models\PaymentSession;
use App\Domain\Subscription\Models\Subscription;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SubscriptionPaymentApproved
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Subscription $subscription,
        public readonly PaymentSession $session,
    ) {}
}
