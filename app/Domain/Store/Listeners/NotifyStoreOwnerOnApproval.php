<?php

namespace App\Domain\Store\Listeners;

use App\Domain\Notification\Services\NotificationService;
use App\Domain\Store\Events\StoreApproved;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class NotifyStoreOwnerOnApproval implements ShouldQueue
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    public function handle(StoreApproved $event): void
    {
        try {
            $this->notificationService->send(
                user: $event->store->owner,
                type: 'store_approved',
                title: 'Store Approved!',
                message: "Congratulations! Your store '{$event->store->name}' has been approved and is now active.",
                channel: 'email',
                data: [
                    'store_id' => $event->store->id,
                    'store_name' => $event->store->name,
                    'approved_at' => $event->store->approved_at->toIso8601String(),
                ]
            );
        } catch (\Exception $e) {
            Log::error('Failed to send store approval notification', [
                'store_id' => $event->store->id,
                'owner_id' => $event->store->owner_user_id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
