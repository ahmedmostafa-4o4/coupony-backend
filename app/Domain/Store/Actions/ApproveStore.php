<?php

namespace App\Domain\Store\Actions;

use App\Domain\Store\Enums\StoreStatus;
use App\Domain\Store\Events\StoreApproved;
use App\Domain\Store\Models\Store;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ApproveStore
{
    public function execute(Store $store, User $admin, ?string $notes = null): Store
    {
        if ($store->status !== StoreStatus::PENDING) {
            throw new \Exception('Only pending stores can be approved.');
        }

        return DB::transaction(function () use ($store, $admin, $notes) {
            // Update store status
            $store->update([
                'status' => StoreStatus::ACTIVE,
                'approved_at' => now(),
                'approved_by' => $admin->id,
                'admin_notes' => $notes,
            ]);

            // Update owner role from seller_pending to seller
            $owner = $store->owner;
            if ($owner->hasRole('seller_pending')) {
                $owner->removeRole('seller_pending');
                $owner->assignRole('seller');
            }

            // Mark verifications as approved
            $store->verifications()->update([
                'status' => 'approved',
                'verified_at' => now(),
                'verified_by' => $admin->id,
            ]);

            // Dispatch event
            event(new StoreApproved($store, $admin));

            Log::info('Store approved', [
                'store_id' => $store->id,
                'admin_id' => $admin->id,
                'owner_id' => $owner->id,
            ]);

            return $store->fresh();
        });
    }
}
