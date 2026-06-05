<?php

namespace App\Listeners;

use App\Domain\Product\Events\ProductRevisionSubmitted;
use App\Domain\Store\Events\StoreCreated;
use App\Domain\Store\Events\StoreLimitReached;
use App\Domain\Subscription\Enums\SubscriptionStatus;
use App\Domain\Subscription\Events\SubscriptionStatusChanged;
use App\Domain\User\Models\User;
use App\Notifications\Admin\NewProductRevisionNotification;
use App\Notifications\Admin\NewStoreRegistrationNotification;
use App\Notifications\Admin\StoreLimitReachedNotification;
use App\Notifications\Admin\SubscriptionCancelledNotification;
use Illuminate\Support\Facades\Notification;

class AdminNotificationSubscriber
{
    /**
     * Get the admins to notify.
     */
    protected function getAdmins()
    {
        return User::role('admin')->get();
    }

    public function handleStoreCreated(StoreCreated $event): void
    {
        Notification::send($this->getAdmins(), new NewStoreRegistrationNotification($event->store));
    }

    public function handleStoreLimitReached(StoreLimitReached $event): void
    {
        Notification::send($this->getAdmins(), new StoreLimitReachedNotification(
            $event->store,
            $event->limitType,
            $event->currentValue,
            $event->maxValue
        ));
    }

    public function handleSubscriptionStatusChanged(SubscriptionStatusChanged $event): void
    {
        if ($event->newStatus === SubscriptionStatus::ARCHIVED || $event->newStatus === SubscriptionStatus::CANCELLED) {
            Notification::send($this->getAdmins(), new SubscriptionCancelledNotification($event->subscription));
        }
    }

    public function handleProductRevisionSubmitted(ProductRevisionSubmitted $event): void
    {
        Notification::send($this->getAdmins(), new NewProductRevisionNotification($event->revision));
    }

    /**
     * Register the listeners for the subscriber.
     */
    public function subscribe($events): array
    {
        return [
            StoreCreated::class => 'handleStoreCreated',
            StoreLimitReached::class => 'handleStoreLimitReached',
            SubscriptionStatusChanged::class => 'handleSubscriptionStatusChanged',
            ProductRevisionSubmitted::class => 'handleProductRevisionSubmitted',
        ];
    }
}
