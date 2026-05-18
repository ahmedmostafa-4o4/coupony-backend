<?php

namespace App\Domain\Store\Actions;

use App\Domain\Store\Events\StaffAssignedToStore;
use App\Domain\Store\Models\Store;
use App\Domain\Store\Models\StoreEmployee;
use App\Domain\User\Models\User;
use Spatie\Permission\Models\Role;

class AssignStaffToStore
{
    public function execute(Store $store, User $staff, string $roleName = 'store_employee', ?User $assignedBy = null): void
    {
        Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'sanctum']);

        if (! $staff->hasRole($roleName)) {
            $staff->assignRole($roleName);
        }

        StoreEmployee::query()->updateOrCreate([
            'store_id' => $store->id,
            'user_id' => $staff->id,
        ], [
            'updated_at' => now(),
        ]);

        event(new StaffAssignedToStore($store, $staff, $roleName, $assignedBy));
    }
}
