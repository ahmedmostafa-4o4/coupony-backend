<?php

namespace App\Domain\Subscription\Actions;

use App\Domain\Subscription\Enums\SubscriptionStatus;
use App\Domain\Subscription\Events\SubscriptionStatusChanged;
use App\Domain\Subscription\Models\Subscription;
use App\Domain\Subscription\Services\SubscriptionStateMachine;

class TransitionSubscriptionAction
{
    public function __construct(
        private readonly SubscriptionStateMachine $stateMachine,
    ) {}

    /**
     * Transition a subscription to a new status.
     *
     * Delegates validation and persistence to the SubscriptionStateMachine,
     * then dispatches a SubscriptionStatusChanged event.
     *
     * @throws \App\Domain\Subscription\Exceptions\InvalidStateTransitionException
     */
    public function execute(Subscription $subscription, SubscriptionStatus $newStatus, string $reason): Subscription
    {
        $previousStatus = $subscription->status;

        $subscription = $this->stateMachine->transition($subscription, $newStatus, $reason);

        SubscriptionStatusChanged::dispatch($subscription, $previousStatus, $newStatus);

        return $subscription;
    }
}
