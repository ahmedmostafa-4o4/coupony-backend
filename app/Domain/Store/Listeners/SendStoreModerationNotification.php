<?php

namespace App\Domain\Store\Listeners;

use App\Domain\Notification\Services\NotificationService;
use App\Domain\Store\Enums\StoreStatus;
use App\Domain\Store\Events\StoreApproved;
use App\Domain\Store\Events\StoreRejected;
use App\Domain\Store\Models\Store;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendStoreModerationNotification implements ShouldQueue
{
    public function __construct(private readonly NotificationService $notifications) {}

    public function handle(StoreApproved|StoreRejected $event): void
    {
        $store = $event->store->fresh() ?? $event->store;
        $store->loadMissing('owner');

        if (! $store->owner) {
            return;
        }

        $isApproved = $event instanceof StoreApproved;

        try {
            $this->notifications->send(
                user: $store->owner,
                type: $isApproved ? 'store_approved' : 'store_rejected',
                title: $isApproved ? 'Store approved' : 'Store rejected',
                message: $isApproved
                    ? 'Your store has been approved.'
                    : 'Your store verification was rejected.',
                channel: 'in_app',
                data: $isApproved
                    ? [
                        'store_id' => $store->id,
                        'status' => $this->storeStatusValue($store),
                        'approved_at' => $store->approved_at?->toIso8601String(),
                    ]
                    : [
                        'store_id' => $store->id,
                        'status' => $this->storeStatusValue($store),
                        'rejection_reason' => $store->rejection_reason,
                    ],
                referenceType: Store::class,
                referenceId: $store->id,
            );
        } catch (Throwable $e) {
            Log::error('Store moderation notification failed', [
                'store_id' => $store->id,
                'owner_id' => $store->owner_user_id,
                'event' => $event::class,
                'error' => $e->getMessage(),
                'exception' => $e::class,
            ]);
        }
    }

    private function storeStatusValue(Store $store): string
    {
        return $store->status instanceof StoreStatus
            ? $store->status->value
            : (string) $store->status;
    }
}
