<?php

namespace App\Domain\Store\Actions;

use App\Domain\Store\Models\Store;
use App\Domain\User\Models\Address;

class DeleteStoreAddress
{
    public function execute(Store $store, Address $address): void
    {
        $store->addresses()->detach($address->id);

        $hasOtherOwners = $address->users()->exists() || $address->stores()->exists();

        if (!$hasOtherOwners) {
            $address->delete();
        }
    }
}
