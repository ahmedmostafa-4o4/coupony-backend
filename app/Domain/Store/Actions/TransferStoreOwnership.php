<?php

namespace App\Domain\Store\Actions;

use App\Domain\Store\Events\StoreOwnershipTransferred;
use App\Domain\Store\Models\Store;
use App\Domain\User\Models\User;
use DB;
use Spatie\Permission\Models\Role;

class TransferStoreOwnership
{
    public function execute(Store $store, User $currentOwner, User $newOwner): void
    {
        return DB::transaction(function () use ($store, $currentOwner, $newOwner) {
            // Update store owner
            $store->update([
                'owner_user_id' => $newOwner->id,
            ]);

            // Assign seller role to new owner
            if (! $newOwner->hasRole('seller')) {
                $newOwner->assignRole('seller');
            }

            // Create user_role record for new owner
            $newOwner->userRoles()->updateOrCreate(
                [
                    'user_id' => $newOwner->id,
                    'store_id' => $store->id,
                ],
                [
                    'role_id' => Role::where('name', 'seller')->first()->id,
                    'granted_at' => now(),
                    'granted_by_user_id' => $currentOwner->id,
                ]
            );

            // // Convert previous owner to staff (optional)
            // $currentOwner->userRoles()
            //     ->where('store_id', $store->id)
            //     ->update([
            //         'role_id' => Role::where('name', 'store_manager')->first()->id,
            //     ]);

            // Dispatch event
            event(new StoreOwnershipTransferred($store, $newOwner, $currentOwner));

            return $store->fresh(['owner']);
        });
    }
}
