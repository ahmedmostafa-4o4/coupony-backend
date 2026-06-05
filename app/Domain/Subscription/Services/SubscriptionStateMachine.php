<?php

namespace App\Domain\Subscription\Services;

use App\Domain\Subscription\Enums\SubscriptionStatus;
use App\Domain\Subscription\Exceptions\InvalidStateTransitionException;
use App\Domain\Subscription\Models\Subscription;
use App\Domain\Subscription\Models\SubscriptionAuditLog;

class SubscriptionStateMachine
{
    /**
     * Map of allowed transitions: from_status => [to_status, ...]
     *
     * @var array<string, array<string>>
     */
    private const ALLOWED_TRANSITIONS = [
        'none' => ['trial', 'active', 'archived'],
        'trial' => ['active', 'none', 'suspended', 'archived'],
        'active' => ['grace', 'suspended', 'archived'],
        'grace' => ['active', 'degraded', 'suspended', 'archived'],
        'degraded' => ['active', 'suspended', 'archived'],
        'suspended' => ['active', 'archived'],
        'archived' => ['active'], // allow un-archiving by an admin if needed
    ];

    /**
     * Determine if a transition from one status to another is allowed.
     */
    public function canTransition(SubscriptionStatus $from, SubscriptionStatus $to): bool
    {
        $allowed = self::ALLOWED_TRANSITIONS[$from->value] ?? [];

        return in_array($to->value, $allowed, true);
    }

    /**
     * Transition a subscription to a new status.
     *
     * @throws InvalidStateTransitionException
     */
    public function transition(Subscription $subscription, SubscriptionStatus $to, string $reason): Subscription
    {
        $from = $subscription->status;

        if (! $this->canTransition($from, $to)) {
            throw InvalidStateTransitionException::make($from, $to);
        }

        $subscription->update(['status' => $to]);

        SubscriptionAuditLog::create([
            'store_id' => $subscription->store_id,
            'subscription_id' => $subscription->id,
            'event_type' => 'status_change',
            'previous_status' => $from->value,
            'new_status' => $to->value,
            'reason' => $reason,
        ]);

        return $subscription->fresh();
    }

    /**
     * Get all allowed target statuses from the given current status.
     *
     * @return array<SubscriptionStatus>
     */
    public function getAllowedTransitions(SubscriptionStatus $current): array
    {
        $allowed = self::ALLOWED_TRANSITIONS[$current->value] ?? [];

        return array_map(
            fn (string $value) => SubscriptionStatus::from($value),
            $allowed
        );
    }
}
