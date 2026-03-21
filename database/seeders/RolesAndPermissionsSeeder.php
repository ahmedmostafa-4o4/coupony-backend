<?php

namespace Database\Seeders;

use App\Domain\User\Enums\UserStatus;
use App\Domain\User\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create roles
        $seller = Role::create(['name' => 'seller']);
        $seller_pending = Role::create(['name' => 'seller_pending']);
        $storeManager = Role::create(['name' => 'store_manager']);
        $storeStaff = Role::create(['name' => 'store_staff']);
        $admin = Role::create(['name' => 'admin']);
        $customer = Role::create(['name' => 'customer']);

        // Create permissions
        Permission::create(['name' => 'store:create']);
        Permission::create(['name' => 'store:manage']);
        Permission::create(['name' => 'product:create']);
        Permission::create(['name' => 'product:update']);
        Permission::create(['name' => 'product:delete']);
        Permission::create(['name' => 'order:view']);
        Permission::create(['name' => 'order:update']);
        Permission::create(['name' => 'product:read']);
        Permission::create(['name' => 'order:create']);
        Permission::create(['name' => 'order:read']);
        Permission::create(['name' => 'cart:manage']);
        Permission::create(['name' => 'profile:update']);

        $customer->givePermissionTo([
            'product:read',
            'order:create',
            'order:read',
            'cart:manage',
            'profile:update',
        ]);

        // Assign permissions to roles
        $seller->givePermissionTo([
            'store:create',
            'store:manage',
            'product:create',
            'product:update',
            'product:delete',
            'order:view',
            'order:update',
        ]);

        $storeManager->givePermissionTo([
            'store:manage',
            'product:create',
            'product:update',
            'order:view',
            'order:update',
        ]);

        $storeStaff->givePermissionTo([
            'product:create',
            'order:view',
            'order:update',
        ]);

        $admin = User::create([
            'email' => 'super.admin@coupony.shop',
            'password_hash' => bcrypt('password'),
            'status' => UserStatus::ACTIVE,
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
        ])->assignRole($admin);

        $admin->profile()->create(['first_name' => 'Coupony', 'last_name' => 'App']);

        $admin->userRoles()->create(['user_id' => $admin->id, 'role_id' => $admin->roles()->where('name', 'admin')->first()->id, 'granted_by_user_id' => $admin->id]);
    }
}
