<?php

namespace App\Domain\Subscription\Events;

use App\Domain\Subscription\Models\PaymentSession;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SubscriptionPaymentFailed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly PaymentSession $session,
        public readonly ?string $reason = null,
    ) {}
}
