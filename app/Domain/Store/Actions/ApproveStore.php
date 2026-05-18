<?php

namespace App\Domain\Store\Actions;

use App\Domain\Store\Enums\StoreStatus;
use App\Domain\Store\Events\StoreApproved;
use App\Domain\Store\Models\Store;
use App\Domain\User\Models\User;
use App\Domain\User\Models\UserRoles;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class ApproveStore
{
    public function execute(Store $store, User $admin, ?string $notes = null): Store
    {
        if ($store->status !== StoreStatus::PENDING) {
            throw new \Exception('Only pending stores can be approved.');
        }

        return DB::transaction(function () use ($store, $admin, $notes) {
            $store->update([
                'status' => StoreStatus::ACTIVE,
                'approved_at' => now(),
                'approved_by' => $admin->id,
                'admin_notes' => $notes,
            ]);

            $owner = $store->owner;
            $sellerPendingRoleId = Role::where('name', 'seller_pending')->value('id');
            $sellerRoleId = Role::where('name', 'seller')->value('id');

            if ($sellerPendingRoleId === null || $sellerRoleId === null) {
                throw new \RuntimeException('Seller roles are not configured.');
            }

            if ($owner->hasRole('seller_pending')) {
                $owner->removeRole('seller_pending');
            }

            if (! $owner->hasRole('seller')) {
                $owner->assignRole('seller');
            }

            UserRoles::where([
                'user_id' => $owner->id,
                'role_id' => $sellerPendingRoleId,
                'store_id' => null,
            ])->delete();

            UserRoles::firstOrCreate([
                'user_id' => $owner->id,
                'role_id' => $sellerRoleId,
                'store_id' => null,
            ], [
                'granted_at' => now(),
                'granted_by_user_id' => $admin->id,
            ]);

            UserRoles::firstOrCreate([
                'user_id' => $owner->id,
                'role_id' => $sellerRoleId,
                'store_id' => $store->id,
            ], [
                'granted_at' => now(),
                'granted_by_user_id' => $admin->id,
            ]);

            $store->verifications()->update([
                'status' => 'approved',
                'verified_at' => now(),
                'verified_by' => $admin->id,
            ]);

            event(new StoreApproved($store, $admin));

            return $store->fresh();
        });
    }
}
