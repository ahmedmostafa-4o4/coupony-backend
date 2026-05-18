<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleAndPermissionSeeder extends Seeder
{
    private const GUARD = 'sanctum';

    /**
     * Canonical store employee permission names.
     */
    private const STORE_PERMISSIONS = [
        'store.dashboard.view',

        'store.products.view',
        'store.products.create',
        'store.products.update',
        'store.products.delete',
        'store.products.manage',

        'store.offers.view',
        'store.offers.create',
        'store.offers.update',
        'store.offers.delete',
        'store.offers.manage',

        'store.claims.view',
        'store.claims.redeem',
        'store.claims.cancel',
        'store.claims.export',
        'store.claims.manage',

        'store.orders.view',
        'store.orders.update',
        'store.orders.cancel',
        'store.orders.refund',
        'store.orders.manage',

        'store.employees.view',
        'store.employees.invite',
        'store.employees.update',
        'store.employees.remove',
        'store.employees.manage',

        'store.branches.view',
        'store.branches.create',
        'store.branches.update',
        'store.branches.delete',
        'store.branches.manage',

        'store.settings.view',
        'store.settings.update',
        'store.settings.manage',

        'store.analytics.view',
        'store.reviews.view',
        'store.reviews.moderate',
        'store.notifications.manage',
    ];

    private const ROLE_PERMISSIONS = [
        'store_manager' => [
            'store.dashboard.view',
            'store.products.manage',
            'store.offers.manage',
            'store.claims.manage',
            'store.orders.manage',
            'store.employees.view',
            'store.branches.view',
            'store.analytics.view',
            'store.reviews.view',
        ],
        'store_employee' => [
            'store.claims.view',
            'store.claims.redeem',
        ],
        'cashier' => [
            'store.claims.view',
            'store.claims.redeem',
            'store.orders.view',
        ],
        'branch_manager' => [
            'store.dashboard.view',
            'store.claims.manage',
            'store.orders.view',
            'store.employees.view',
            'store.branches.view',
        ],
        'inventory_manager' => [
            'store.products.view',
            'store.products.create',
            'store.products.update',
            'store.offers.view',
            'store.offers.update',
        ],
        'content_manager' => [
            'store.products.manage',
            'store.offers.manage',
            'store.reviews.view',
        ],
        'support_agent' => [
            'store.orders.view',
            'store.claims.view',
            'store.reviews.view',
        ],
        'seller' => [
            'store.dashboard.view',
            'store.products.manage',
            'store.orders.view',
            'store.analytics.view',
        ],
        'seller_pending' => [
            'store.dashboard.view',
        ],
        'customer' => [],
    ];

    private const LEGACY_PERMISSIONS = [
        'view users',
        'create users',
        'edit users',
        'delete users',
        'view stores',
        'create stores',
        'edit stores',
        'delete stores',
        'approve stores',
        'reject stores',
        'view categories',
        'create categories',
        'edit categories',
        'delete categories',
        'view orders',
        'create orders',
        'edit orders',
        'delete orders',
        'view'.' claims',
        'redeem'.' claims',
        'view reports',
        'generate reports',
        'store'.':'.'create',
        'store'.':'.'manage',
        'product'.':'.'create',
        'product'.':'.'update',
        'product'.':'.'delete',
        'product'.':'.'read',
        'order'.':'.'view',
        'order'.':'.'update',
        'order'.':'.'create',
        'order'.':'.'read',
        'cart'.':'.'manage',
        'profile'.':'.'update',
        'claim'.':'.'view',
        'claim'.':'.'redeem',
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Permission::query()
            ->whereIn('name', self::LEGACY_PERMISSIONS)
            ->delete();

        foreach (self::STORE_PERMISSIONS as $permission) {
            Permission::query()->firstOrCreate([
                'name' => $permission,
                'guard_name' => self::GUARD,
            ]);
        }

        foreach ($this->roleNames() as $roleName) {
            Role::query()->firstOrCreate([
                'name' => $roleName,
                'guard_name' => self::GUARD,
            ]);
        }

        $allPermissions = Permission::query()
            ->where('guard_name', self::GUARD)
            ->pluck('name')
            ->all();

        $allStorePermissions = Permission::query()
            ->where('guard_name', self::GUARD)
            ->whereIn('name', self::STORE_PERMISSIONS)
            ->pluck('name')
            ->all();

        foreach (['super_admin', 'admin'] as $roleName) {
            Role::query()
                ->where('name', $roleName)
                ->where('guard_name', self::GUARD)
                ->firstOrFail()
                ->syncPermissions($allPermissions);
        }

        Role::query()
            ->where('name', 'store_owner')
            ->where('guard_name', self::GUARD)
            ->firstOrFail()
            ->syncPermissions($allStorePermissions);

        foreach (self::ROLE_PERMISSIONS as $roleName => $permissions) {
            Role::query()
                ->where('name', $roleName)
                ->where('guard_name', self::GUARD)
                ->firstOrFail()
                ->syncPermissions($permissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->command?->info('Roles and permissions synced successfully.');
    }

    private function roleNames(): array
    {
        return [
            'super_admin',
            'admin',
            'seller',
            'seller_pending',
            'customer',
            'store_owner',
            'store_manager',
            'store_employee',
            'branch_manager',
            'cashier',
            'inventory_manager',
            'content_manager',
            'support_agent',
        ];
    }
}
