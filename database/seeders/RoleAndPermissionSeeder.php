<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // User permissions
            'view users',
            'create users',
            'edit users',
            'delete users',

            // Store permissions
            'view stores',
            'create stores',
            'edit stores',
            'delete stores',
            'approve stores',
            'reject stores',

            // Category permissions
            'view categories',
            'create categories',
            'edit categories',
            'delete categories',

            // Order permissions
            'view orders',
            'create orders',
            'edit orders',
            'delete orders',

            // Claim permissions
            'view claims',
            'redeem claims',

            // Report permissions
            'view reports',
            'generate reports',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission, 'guard_name' => 'sanctum']);
        }

        // Create roles and assign permissions

        // Admin role
        $admin = Role::create(['name' => 'admin', 'guard_name' => 'sanctum']);
        $admin->givePermissionTo(Permission::all());

        // Seller role
        $seller = Role::create(['name' => 'seller', 'guard_name' => 'sanctum']);
        $seller->givePermissionTo([
            'view stores',
            'edit stores',
            'view orders',
            'create orders',
            'edit orders',
        ]);

        // Seller Pending role
        $sellerPending = Role::create(['name' => 'seller_pending', 'guard_name' => 'sanctum']);
        $sellerPending->givePermissionTo([
            'view stores',
        ]);

        // Customer role
        $customer = Role::create(['name' => 'customer', 'guard_name' => 'sanctum']);
        $customer->givePermissionTo([
            'view stores',
            'view categories',
            'create orders',
            'view orders',
        ]);

        // Store Manager role
        $storeManager = Role::create(['name' => 'store_manager', 'guard_name' => 'sanctum']);
        $storeManager->givePermissionTo([
            'view stores',
            'edit stores',
            'view orders',
            'edit orders',
        ]);

        // Store Employee role
        $storeEmployee = Role::create(['name' => 'store_employee', 'guard_name' => 'sanctum']);
        $storeEmployee->givePermissionTo([
            'view claims',
            'redeem claims',
        ]);

        $this->command->info('Roles and permissions created successfully!');
    }
}
