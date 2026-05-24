<?php

namespace App\Domain\Store\Listeners;

use App\Domain\Notification\Services\NotificationService;
use App\Domain\Notification\Support\NotificationMessageResolver;
use App\Domain\Store\Events\StoreFollowed;
use App\Domain\Store\Models\Store;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendNewFollowerNotification implements ShouldQueue
{
    public function __construct(private readonly NotificationService $notifications) {}

    public function handle(StoreFollowed $event): void
    {
        $store = $event->store;
        $follower = $event->follower;

        $store->loadMissing('owner');
        $follower->loadMissing('profile');

        $owner = $store->owner;

        if (! $owner) {
            return;
        }

        // Don't notify if the owner follows their own store
        if ((string) $owner->id === (string) $follower->id) {
            return;
        }

        $followerName = $follower->full_name ?? $follower->name ?? $follower->email;
        $followerAvatar = $follower->avatar ?? $follower->profile?->avatar_url ?? null;

        $resolved = NotificationMessageResolver::resolve('new_follower', [
            'follower_name' => $followerName,
        ], $owner);

        try {
            $this->notifications->send(
                user: $owner,
                type: 'new_follower',
                title: $resolved['title'],
                message: $resolved['message'],
                channel: 'in_app',
                data: [
                    'follower_id' => $follower->id,
                    'store_id' => $store->id,
                ],
                referenceType: Store::class,
                referenceId: $store->id,
                imageUrl: $followerAvatar,
            );
        } catch (Throwable $e) {
            Log::error('New follower notification failed', [
                'store_id' => $store->id,
                'follower_id' => $follower->id,
                'owner_id' => $owner->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
