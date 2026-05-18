<?php

namespace App\Domain\Store\Actions;

use App\Domain\Store\Enums\StoreStatus;
use App\Domain\Store\Models\Store;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\DB;

class CloseStore
{
    public function execute(Store $store, User $admin, ?string $reason = null): Store
    {
        if ($store->status === StoreStatus::CLOSED) {
            throw new \Exception('Store is already closed.');
        }

        return DB::transaction(function () use ($store, $reason) {
            $store->update([
                'status' => StoreStatus::CLOSED,
                'admin_notes' => $reason,
            ]);

            return $store->fresh();
        });
    }
}
