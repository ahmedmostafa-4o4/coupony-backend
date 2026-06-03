<?php

namespace App\Domain\Role\Actions\Admin;
use Spatie\Permission\Models\Role;
use Illuminate\Validation\ValidationException;

class DeleteRoleAction
{
    private array $systemRoles = ['admin', 'seller', 'customer', 'seller_pending'];

    public function execute(Role $role): void
    {
        if (in_array($role->name, $this->systemRoles)) {
            throw ValidationException::withMessages([
                'role' => [__('api.admin.roles.cannot_delete_system_role')]
            ]);
        }

        if (\Illuminate\Support\Facades\DB::table('model_has_roles')->where('role_id', $role->id)->count() > 0) {
            throw ValidationException::withMessages([
                'role' => [__('api.admin.roles.cannot_delete_role_with_users')]
            ]);
        }

        $role->delete();
    }
}
