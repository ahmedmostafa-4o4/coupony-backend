<?php

namespace App\Domain\Store\Actions;

use App\Domain\Store\Events\StaffRemovedFromStore;
use App\Domain\Store\Models\Store;
use App\Domain\User\Models\User;

class RemoveStaffFromStore
{
    public function execute(Store $store, User $staff): void
    {
        $staff->userRoles()
            ->where('store_id', $store->id)
            ->delete();

        event(new StaffRemovedFromStore($store, $staff));
    }
}
