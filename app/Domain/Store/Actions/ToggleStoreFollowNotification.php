<?php

namespace App\Domain\Store\Actions;

use App\Domain\Store\Models\Store;
use App\Domain\Store\Models\StoreFollowers;
use App\Domain\User\Models\User;

class ToggleStoreFollowNotification
{
    public function execute(Store $store, User $user): ?StoreFollowers
    {
        $follow = StoreFollowers::where('store_id', $store->id)
            ->where('user_id', $user->id)
            ->first();

        if (!$follow) {
            return null;
        }

        $follow->update([
            'notification_enabled' => !$follow->notification_enabled,
        ]);

        return $follow->fresh();
    }
}
