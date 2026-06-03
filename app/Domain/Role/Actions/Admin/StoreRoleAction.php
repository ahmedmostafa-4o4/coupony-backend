<?php

namespace App\Domain\Role\Actions\Admin;

use App\Domain\Role\DTOs\Admin\RoleDTO;
use Spatie\Permission\Models\Role;

class StoreRoleAction
{
    public function execute(RoleDTO $dto): Role
    {
        $role = Role::create([
            'name' => $dto->name,
            'guard_name' => 'sanctum',
        ]);

        if (is_array($dto->permissions)) {
            $role->syncPermissions($dto->permissions);
        }

        return $role;
    }
}
