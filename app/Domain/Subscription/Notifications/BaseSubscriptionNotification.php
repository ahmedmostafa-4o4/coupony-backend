<?php

namespace App\Domain\Subscription\Notifications;

use App\Domain\Notification\Services\NotificationService;
use App\Domain\Subscription\Models\Subscription;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\Log;
use Throwable;

abstract class BaseSubscriptionNotification
{
    abstract public function type(): string;

    abstract public function title(): string;

    abstract public function message(): string;

    abstract public function data(): array;

    public function channel(): string
    {
        return 'in_app';
    }

    public function referenceType(): string
    {
        return Subscription::class;
    }

    abstract public function referenceId(): string;

    /**
     * Send the notification to the given user via the NotificationService.
     */
    public function send(User $user, NotificationService $notificationService): void
    {
        try {
            $notificationService->send(
                user: $user,
                type: $this->type(),
                title: $this->title(),
                message: $this->message(),
                channel: $this->channel(),
                data: $this->data(),
                referenceType: $this->referenceType(),
                referenceId: $this->referenceId(),
            );
        } catch (Throwable $e) {
            Log::error('Subscription notification failed', [
                'user_id' => $user->id,
                'type' => $this->type(),
                'reference_type' => $this->referenceType(),
                'reference_id' => $this->referenceId(),
                'error' => $e->getMessage(),
                'exception' => $e::class,
            ]);
        }
    }
}
