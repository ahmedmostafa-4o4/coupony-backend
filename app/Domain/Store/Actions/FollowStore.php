<?php

namespace App\Domain\Store\Actions;

use App\Domain\Store\Events\StoreFollowed;
use App\Domain\Store\Models\Store;
use App\Domain\Store\Models\StoreFollowers;
use App\Domain\User\Models\User;

class FollowStore
{
    public function execute(Store $store, User $user): StoreFollowers
    {
        $follow = StoreFollowers::firstOrCreate(
            [
                'store_id' => $store->id,
                'user_id' => $user->id,
            ],
            [
                'notification_enabled' => true,
                'followed_at' => now(),
            ]
        );

        // Update denormalized count if a new record was created
        if ($follow->wasRecentlyCreated) {
            Store::where('id', $store->id)->increment('followers_count');

            event(new StoreFollowed($store, $user));
        }

        return $follow;
    }
}
