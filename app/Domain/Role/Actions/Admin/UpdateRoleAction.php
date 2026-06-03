<?php

namespace App\Domain\Role\Actions\Admin;

use App\Domain\Role\DTOs\Admin\RoleDTO;
use Spatie\Permission\Models\Role;
use Illuminate\Validation\ValidationException;

class UpdateRoleAction
{
    private array $systemRoles = ['admin', 'seller', 'customer', 'seller_pending'];

    public function execute(Role $role, RoleDTO $dto): Role
    {
        if (in_array($role->name, $this->systemRoles)) {
            throw ValidationException::withMessages([
                'role' => [__('api.admin.roles.cannot_update_system_role')]
            ]);
        }

        if ($dto->name) {
            $role->name = $dto->name;
            $role->save();
        }

        if (is_array($dto->permissions)) {
            $role->syncPermissions($dto->permissions);
        }

        return $role;
    }
}
