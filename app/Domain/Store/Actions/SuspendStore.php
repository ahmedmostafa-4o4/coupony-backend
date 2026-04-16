<?php

namespace App\Domain\Store\Actions;

use App\Domain\Store\Enums\StoreStatus;
use App\Domain\Store\Models\Store;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\DB;

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

            return $store->fresh();
        });
    }
}
