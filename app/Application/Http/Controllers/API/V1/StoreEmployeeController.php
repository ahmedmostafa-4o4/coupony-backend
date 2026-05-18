<?php

namespace App\Application\Http\Controllers\API\V1;

use App\Application\Http\Controllers\Controller;
use App\Application\Http\Requests\UpdateStoreEmployeeRequest;
use App\Application\Http\Resources\StoreEmployeeResource;
use App\Domain\Store\Enums\StorePermission;
use App\Domain\Store\Models\Store;
use App\Domain\Store\Models\StoreEmployee;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class StoreEmployeeController extends Controller
{
    use AuthorizesRequests;

    private const STORE_SPECIFIC_ROLES = [
        'store_manager',
        'store_employee',
        'branch_manager',
        'cashier',
        'inventory_manager',
        'content_manager',
        'support_agent',
    ];

    public function index(Request $request, Store $store): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);
        $this->authorize('viewEmployees', $store);

        $query = StoreEmployee::query()
            ->with(['user.profile', 'address'])
            ->where('store_id', $store->id);

        if ($request->filled('role')) {
            $query->where('role', $request->query('role'));
        }

        if ($request->filled('address_id')) {
            $query->where('address_id', $request->query('address_id'));
        }

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->whereHas('user', function ($query) use ($search) {
                $query->where('email', 'like', '%'.$search.'%')
                    ->orWhereHas('profile', function ($profileQuery) use ($search) {
                        $profileQuery->where('first_name', 'like', '%'.$search.'%')
                            ->orWhere('last_name', 'like', '%'.$search.'%');
                    });
            });
        }

        $employees = $query->paginate((int) $request->query('per_page', 15));

        return $this->localizedJson([
            'success' => true,
            'data' => StoreEmployeeResource::collection($employees->items()),
            'meta' => [
                'current_page' => $employees->currentPage(),
                'last_page' => $employees->lastPage(),
                'per_page' => $employees->perPage(),
                'total' => $employees->total(),
            ],
        ]);
    }

    public function show(Request $request, Store $store, User $user): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);
        $this->authorize('viewEmployees', $store);

        $employee = $this->employeeFor($store, $user);

        return $this->localizedJson([
            'success' => true,
            'data' => new StoreEmployeeResource($employee),
        ]);
    }

    public function update(UpdateStoreEmployeeRequest $request, Store $store, User $user): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        if ((string) $store->owner_user_id === (string) $user->id) {
            return $this->localizedJson([
                'success' => false,
                'message' => __('api.invitation.cannot_update_owner'),
            ], 422);
        }

        if ((string) $request->user()->id === (string) $user->id
            && (string) $store->owner_user_id !== (string) $request->user()->id) {
            return $this->localizedJson([
                'success' => false,
                'message' => __('api.invitation.cannot_update_self'),
            ], 422);
        }

        $employee = $this->employeeFor($store, $user);
        $validated = $request->validated();

        DB::transaction(function () use ($employee, $user, $validated) {
            $oldRole = $employee->role;

            $employee->fill(array_intersect_key($validated, array_flip([
                'address_id',
                'role',
                'permissions',
            ])));
            $employee->save();

            if (array_key_exists('role', $validated) && $validated['role'] !== $oldRole) {
                $this->assignStoreSpecificRole($user, $validated['role']);
                $this->removeUnusedStoreSpecificRole($user, $oldRole, $employee);
            }
        });

        return $this->localizedJson([
            'success' => true,
            'data' => new StoreEmployeeResource($employee->load(['user.profile', 'address'])),
        ]);
    }

    public function destroy(Request $request, Store $store, User $user): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);
        $this->authorize('removeEmployees', $store);

        if ((string) $store->owner_user_id === (string) $user->id) {
            return $this->localizedJson([
                'success' => false,
                'message' => __('api.invitation.cannot_remove_owner'),
            ], 422);
        }

        $employee = $this->employeeFor($store, $user);

        DB::transaction(function () use ($employee, $user) {
            $employee->delete();
            $this->removeStoreSpecificRolesIfNoEmployeeRowsRemain($user);
        });

        return $this->localizedJson([
            'success' => true,
            'message' => __('api.invitation.employee_removed'),
        ]);
    }

    public function permissions(Request $request): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        $labels = StorePermission::labels();
        $groups = collect(StorePermission::grouped())
            ->map(fn (array $permissions, string $group) => [
                'group' => $group,
                'permissions' => collect($permissions)
                    ->map(fn (string $permission) => [
                        'key' => $permission,
                        'label' => $labels[$permission] ?? $permission,
                    ])
                    ->values()
                    ->all(),
            ])
            ->values()
            ->all();

        return $this->localizedJson([
            'success' => true,
            'data' => $groups,
        ]);
    }

    private function employeeFor(Store $store, User $user): StoreEmployee
    {
        return StoreEmployee::query()
            ->with(['user.profile', 'address'])
            ->where('store_id', $store->id)
            ->where('user_id', $user->id)
            ->firstOrFail();
    }

    private function assignStoreSpecificRole(User $user, string $role): void
    {
        Role::query()->firstOrCreate([
            'name' => $role,
            'guard_name' => 'sanctum',
        ]);

        if (! $user->hasRole($role)) {
            $user->assignRole($role);
        }
    }

    private function removeUnusedStoreSpecificRole(User $user, ?string $oldRole, StoreEmployee $currentEmployee): void
    {
        if (! $oldRole || ! in_array($oldRole, self::STORE_SPECIFIC_ROLES, true) || ! $user->hasRole($oldRole)) {
            return;
        }

        $stillUsesOldRole = StoreEmployee::query()
            ->where('user_id', $user->id)
            ->where('role', $oldRole)
            ->whereKeyNot($currentEmployee->id)
            ->exists();

        if (! $stillUsesOldRole) {
            $user->removeRole($oldRole);
        }
    }

    private function removeStoreSpecificRolesIfNoEmployeeRowsRemain(User $user): void
    {
        $hasRemainingEmployeeRows = StoreEmployee::query()
            ->where('user_id', $user->id)
            ->exists();

        if ($hasRemainingEmployeeRows) {
            return;
        }

        foreach (self::STORE_SPECIFIC_ROLES as $role) {
            if ($user->hasRole($role)) {
                $user->removeRole($role);
            }
        }
    }
}
