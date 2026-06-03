<?php

namespace App\Application\Http\Controllers\API\V1\Admin;

use App\Application\Http\Controllers\Controller;
use App\Application\Http\Requests\Admin\RoleManagement\StoreRoleRequest;
use App\Application\Http\Requests\Admin\RoleManagement\UpdateRoleRequest;
use App\Domain\Role\Actions\Admin\DeleteRoleAction;
use App\Domain\Role\Actions\Admin\StoreRoleAction;
use App\Domain\Role\Actions\Admin\UpdateRoleAction;
use App\Domain\Role\DTOs\Admin\RoleDTO;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Log;

class RolePermissionController extends Controller
{
    public function __construct(
        private readonly StoreRoleAction $storeRoleAction,
        private readonly UpdateRoleAction $updateRoleAction,
        private readonly DeleteRoleAction $deleteRoleAction
    ) {
    }

    /**
     * Get a list of all roles.
     */
    public function roles(Request $request): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        $roles = Role::with('permissions:id,name')->get(['id', 'name', 'guard_name']);

        return $this->localizedJson([
            'message' => __('api.admin.roles.retrieved'),
            'data' => $roles,
        ]);
    }

    /**
     * Get a list of all permissions.
     */
    public function permissions(Request $request): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        $permissions = Permission::get(['id', 'name', 'guard_name']);

        return $this->localizedJson([
            'message' => __('api.admin.permissions.retrieved'),
            'data' => $permissions,
        ]);
    }

    /**
     * Get permissions for a specific role.
     */
    public function rolePermissions(Request $request, string $roleName): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        $role = Role::findByName($roleName, 'sanctum');

        return $this->localizedJson([
            'message' => __('api.admin.roles.permissions_retrieved'),
            'data' => [
                'role' => [
                    'id' => $role->id,
                    'name' => $role->name,
                ],
                'permissions' => $role->permissions()->get(['id', 'name', 'guard_name']),
            ],
        ]);
    }

    /**
     * Create a new role.
     */
    public function store(StoreRoleRequest $request): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        try {
            $dto = RoleDTO::fromRequest($request);
            $role = $this->storeRoleAction->execute($dto);
            $role->load('permissions:id,name');

            return $this->localizedJson([
                'message' => __('api.admin.roles.created'),
                'data' => $role,
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Failed to create role', ['error' => $e->getMessage()]);
            return $this->localizedJson([
                'message' => __('api.admin.roles.create_failed'),
            ], 500);
        }
    }

    /**
     * Update an existing role.
     */
    public function update(UpdateRoleRequest $request, Role $role): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        try {
            $dto = RoleDTO::fromRequest($request);
            $role = $this->updateRoleAction->execute($role, $dto);
            $role->load('permissions:id,name');

            return $this->localizedJson([
                'message' => __('api.admin.roles.updated'),
                'data' => $role,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Failed to update role', ['error' => $e->getMessage()]);
            return $this->localizedJson([
                'message' => __('api.admin.roles.update_failed'),
            ], 500);
        }
    }

    /**
     * Delete an existing role.
     */
    public function destroy(Request $request, Role $role): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        try {
            $this->deleteRoleAction->execute($role);

            return $this->localizedJson([
                'message' => __('api.admin.roles.deleted'),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Failed to delete role', ['error' => $e->getMessage()]);
            return $this->localizedJson([
                'message' => __('api.admin.roles.delete_failed'),
            ], 500);
        }
    }
}
