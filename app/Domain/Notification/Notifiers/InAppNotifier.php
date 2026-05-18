<?php

namespace App\Domain\Notification\Notifiers;

use App\Domain\Notification\Contracts\NotifierInterface;
use App\Domain\Notification\Models\Notification;
use App\Domain\User\Models\User;

class InAppNotifier implements NotifierInterface
{
    /**
     * In-app notifications are already stored in DB, just mark as sent.
     */
    public function send(Notification $notification, User $user): void
    {
        // NotificationService broadcasts the canonical NotificationSent event.
    }
}
