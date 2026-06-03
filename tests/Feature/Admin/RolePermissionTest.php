<?php

namespace Tests\Feature\Admin;

use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RolePermissionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::create(['name' => 'admin', 'guard_name' => 'sanctum']);
        $sellerRole = Role::create(['name' => 'seller', 'guard_name' => 'sanctum']);

        $perm1 = Permission::create(['name' => 'view dashboard', 'guard_name' => 'sanctum']);
        $perm2 = Permission::create(['name' => 'manage users', 'guard_name' => 'sanctum']);

        $adminRole->givePermissionTo([$perm1, $perm2]);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
    }

    public function test_admin_can_get_all_roles(): void
    {
        $response = $this->actingAs($this->admin)->getJson('/api/v1/admin/roles');

        $response->assertStatus(200)
            ->assertJsonPath('data.0.name', 'admin')
            ->assertJsonPath('data.1.name', 'seller');
    }

    public function test_admin_can_get_all_permissions(): void
    {
        $response = $this->actingAs($this->admin)->getJson('/api/v1/admin/roles/permissions');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_admin_can_get_permissions_for_specific_role(): void
    {
        $response = $this->actingAs($this->admin)->getJson('/api/v1/admin/roles/admin/permissions');

        $response->assertStatus(200)
            ->assertJsonPath('data.role.name', 'admin')
            ->assertJsonCount(2, 'data.permissions');
    }

    public function test_admin_can_create_a_custom_role(): void
    {
        $response = $this->actingAs($this->admin)->postJson('/api/v1/admin/roles', [
            'name' => 'moderator',
            'permissions' => ['view dashboard'],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'moderator')
            ->assertJsonPath('data.permissions.0.name', 'view dashboard');
            
        $this->assertDatabaseHas('roles', ['name' => 'moderator']);
    }

    public function test_admin_can_update_a_custom_role(): void
    {
        $role = Role::create(['name' => 'editor', 'guard_name' => 'sanctum']);

        $response = $this->actingAs($this->admin)->putJson("/api/v1/admin/roles/{$role->id}", [
            'name' => 'super editor',
            'permissions' => ['manage users'],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'super editor')
            ->assertJsonPath('data.permissions.0.name', 'manage users');

        $this->assertDatabaseHas('roles', ['name' => 'super editor']);
    }

    public function test_admin_cannot_update_system_role(): void
    {
        $adminRole = Role::where('name', 'admin')->first();
        $response = $this->actingAs($this->admin)->putJson("/api/v1/admin/roles/{$adminRole->id}", [
            'name' => 'super admin',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['role']);
            
        $this->assertDatabaseHas('roles', ['name' => 'admin']);
    }

    public function test_admin_can_delete_a_custom_role(): void
    {
        $role = Role::create(['name' => 'editor', 'guard_name' => 'sanctum']);

        $response = $this->actingAs($this->admin)->deleteJson("/api/v1/admin/roles/{$role->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('roles', ['name' => 'editor']);
    }

    public function test_admin_cannot_delete_system_roles(): void
    {
        $adminRole = Role::where('name', 'admin')->first();
        $response = $this->actingAs($this->admin)->deleteJson("/api/v1/admin/roles/{$adminRole->id}");

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['role']);
            
        $this->assertDatabaseHas('roles', ['name' => 'admin']);
    }

    public function test_admin_cannot_delete_role_with_assigned_users(): void
    {
        $role = Role::create(['name' => 'editor', 'guard_name' => 'sanctum']);
        $user = User::factory()->create();
        $user->assignRole('editor');

        $response = $this->actingAs($this->admin)->deleteJson("/api/v1/admin/roles/{$role->id}");

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['role']);
            
        $this->assertDatabaseHas('roles', ['name' => 'editor']);
    }
}
