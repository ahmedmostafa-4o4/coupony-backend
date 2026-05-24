<?php

namespace App\Domain\Store\Listeners;

use App\Domain\Notification\Services\NotificationService;
use App\Domain\Notification\Support\NotificationMessageResolver;
use App\Domain\Store\Events\StoreCreated;
use App\Domain\Store\Models\Store;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendStorePendingNotification implements ShouldQueue
{
    public function __construct(private readonly NotificationService $notifications) {}

    public function handle(StoreCreated $event): void
    {
        $store = $event->store;
        $store->loadMissing('owner');

        $owner = $store->owner;

        if (! $owner) {
            return;
        }

        $resolved = NotificationMessageResolver::resolve('store_pending', [], $owner);

        try {
            $this->notifications->send(
                user: $owner,
                type: 'store_pending',
                title: $resolved['title'],
                message: $resolved['message'],
                channel: 'in_app',
                data: [
                    'store_id' => $store->id,
                    'status' => 'pending',
                ],
                referenceType: Store::class,
                referenceId: $store->id,
                imageUrl: $store->logo_url ?? null,
            );
        } catch (Throwable $e) {
            Log::error('Store pending notification failed', [
                'store_id' => $store->id,
                'owner_id' => $owner->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
