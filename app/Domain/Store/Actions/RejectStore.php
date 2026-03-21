<?php

namespace App\Domain\Store\Actions;

use App\Domain\Store\Enums\StoreStatus;
use App\Domain\Store\Events\StoreRejected;
use App\Domain\Store\Models\Store;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RejectStore
{
    public function execute(Store $store, User $admin, string $reason): Store
    {
        if ($store->status !== StoreStatus::PENDING) {
            throw new \Exception('Only pending stores can be rejected.');
        }

        return DB::transaction(function () use ($store, $admin, $reason) {
            // Update store status
            $store->update([
                'status' => StoreStatus::REJECTED,
                'rejected_at' => now(),
                'rejected_by' => $admin->id,
                'rejection_reason' => $reason,
            ]);

            // Mark verifications as rejected
            $store->verifications()->update([
                'status' => 'rejected',
                'verified_at' => now(),
                'verified_by' => $admin->id,
            ]);

            // Dispatch event
            event(new StoreRejected($store, $admin, $reason));

            Log::info('Store rejected', [
                'store_id' => $store->id,
                'admin_id' => $admin->id,
                'owner_id' => $store->owner_user_id,
                'reason' => $reason,
            ]);

            return $store->fresh();
        });
    }
}
