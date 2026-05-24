<?php

namespace App\Domain\Subscription\Events;

use App\Domain\Subscription\Enums\SubscriptionStatus;
use App\Domain\Subscription\Models\Subscription;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SubscriptionStatusChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Subscription $subscription,
        public readonly SubscriptionStatus $previousStatus,
        public readonly SubscriptionStatus $newStatus,
    ) {}
}
