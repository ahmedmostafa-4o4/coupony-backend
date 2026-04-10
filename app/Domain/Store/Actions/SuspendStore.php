<?php

namespace App\Domain\Store\Actions;

use App\Domain\Store\Enums\StoreStatus;
use App\Domain\Store\Models\Store;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SuspendStore
{
    public function execute(Store $store, User $admin, ?string $reason = null): Store
    {
        if ($store->status === StoreStatus::SUSPENDED) {
            throw new \Exception('Store is already suspended.');
        }

        return DB::transaction(function () use ($store, $admin, $reason) {
            $store->update([
                'status' => StoreStatus::SUSPENDED,
                'admin_notes' => $reason,
            ]);

            Log::info('Store suspended', [
                'store_id' => $store->id,
                'admin_id' => $admin->id,
                'owner_id' => $store->owner_user_id,
                'reason' => $reason,
            ]);

            return $store->fresh();
        });
    }
}
