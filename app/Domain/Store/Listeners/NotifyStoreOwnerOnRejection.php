<?php

namespace App\Domain\Store\Listeners;

use App\Domain\Notification\Services\NotificationService;
use App\Domain\Store\Events\StoreRejected;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class NotifyStoreOwnerOnRejection implements ShouldQueue
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    public function handle(StoreRejected $event): void
    {
        try {
            $this->notificationService->send(
                user: $event->store->owner,
                type: 'store_rejected',
                title: 'Store Registration Rejected',
                message: "Unfortunately, your store registration for '{$event->store->name}' has been rejected. Reason: {$event->reason}",
                channel: 'email',
                data: [
                    'store_id' => $event->store->id,
                    'store_name' => $event->store->name,
                    'rejection_reason' => $event->reason,
                    'rejected_at' => $event->store->rejected_at->toIso8601String(),
                ]
            );
        } catch (\Exception $e) {
            Log::error('Failed to send store rejection notification', [
                'store_id' => $event->store->id,
                'owner_id' => $event->store->owner_user_id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
