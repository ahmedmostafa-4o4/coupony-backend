<?php

namespace App\Domain\Subscription\Listeners;

use App\Domain\Notification\Services\NotificationService;
use App\Domain\Subscription\Enums\SubscriptionStatus;
use App\Domain\Subscription\Events\SubscriptionPaymentApproved;
use App\Domain\Subscription\Events\SubscriptionPaymentFailed;
use App\Domain\Subscription\Events\SubscriptionStatusChanged;
use App\Domain\Subscription\Notifications\SubscriptionDegradedNotification;
use App\Domain\Subscription\Notifications\SubscriptionGraceStartedNotification;
use App\Domain\Subscription\Notifications\SubscriptionPaymentApprovedNotification;
use App\Domain\Subscription\Notifications\SubscriptionPaymentFailedNotification;
use App\Domain\Subscription\Notifications\SubscriptionSuspendedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendSubscriptionNotification implements ShouldQueue
{
    public function __construct(private readonly NotificationService $notifications) {}

    public function handle(SubscriptionPaymentApproved|SubscriptionPaymentFailed|SubscriptionStatusChanged $event): void
    {
        match (true) {
            $event instanceof SubscriptionPaymentApproved => $this->handlePaymentApproved($event),
            $event instanceof SubscriptionPaymentFailed => $this->handlePaymentFailed($event),
            $event instanceof SubscriptionStatusChanged => $this->handleStatusChanged($event),
        };
    }

    private function handlePaymentApproved(SubscriptionPaymentApproved $event): void
    {
        $subscription = $event->subscription;
        $store = $subscription->store;

        if (! $store) {
            return;
        }

        $store->loadMissing('owner');

        if (! $store->owner) {
            return;
        }

        $notification = new SubscriptionPaymentApprovedNotification($subscription, $event->session);
        $notification->send($store->owner, $this->notifications);
    }

    private function handlePaymentFailed(SubscriptionPaymentFailed $event): void
    {
        $session = $event->session;
        $store = $session->store;

        if (! $store) {
            return;
        }

        $store->loadMissing('owner');

        if (! $store->owner) {
            return;
        }

        $notification = new SubscriptionPaymentFailedNotification($session, $event->reason);
        $notification->send($store->owner, $this->notifications);
    }

    private function handleStatusChanged(SubscriptionStatusChanged $event): void
    {
        $subscription = $event->subscription;
        $store = $subscription->store;

        if (! $store) {
            return;
        }

        $store->loadMissing('owner');

        if (! $store->owner) {
            return;
        }

        $notification = match ($event->newStatus) {
            SubscriptionStatus::GRACE => new SubscriptionGraceStartedNotification($subscription),
            SubscriptionStatus::DEGRADED => new SubscriptionDegradedNotification($subscription),
            SubscriptionStatus::SUSPENDED => new SubscriptionSuspendedNotification($subscription),
            default => null,
        };

        if ($notification === null) {
            return;
        }

        $notification->send($store->owner, $this->notifications);
    }
}
