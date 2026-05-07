<?php

namespace App\Domain\Store\Actions;

use App\Domain\Store\Models\Store;
use App\Domain\Store\Models\StoreFollowers;
use App\Domain\User\Models\User;

class UnfollowStore
{
    public function execute(Store $store, User $user): bool
    {
        $deleted = StoreFollowers::where('store_id', $store->id)
            ->where('user_id', $user->id)
            ->delete();

        if ($deleted > 0) {
            Store::where('id', $store->id)->decrement('followers_count');
        }

        return $deleted > 0;
    }
}
