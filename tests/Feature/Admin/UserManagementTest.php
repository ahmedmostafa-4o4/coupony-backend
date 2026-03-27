<?php

namespace Tests\Feature\Admin;

use App\Domain\User\Enums\UserStatus;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        \Spatie\Permission\Models\Role::create(['name' => 'admin', 'guard_name' => 'sanctum']);
        \Spatie\Permission\Models\Role::create(['name' => 'seller_pending', 'guard_name' => 'sanctum']);
        \Spatie\Permission\Models\Role::create(['name' => 'seller', 'guard_name' => 'sanctum']);
        \Spatie\Permission\Models\Role::create(['name' => 'customer', 'guard_name' => 'sanctum']);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
    }

    public function test_admin_can_list_users_with_filters(): void
    {
        $customer = User::factory()->create(['status' => UserStatus::ACTIVE->value]);
        $customer->assignRole('customer');

        $seller = User::factory()->create(['status' => UserStatus::SUSPENDED->value]);
        $seller->assignRole('seller');

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/users?role=seller&status=suspended');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $seller->id);
    }

    public function test_admin_can_view_single_user_details(): void
    {
        $user = User::factory()->create();
        $user->assignRole('customer');

        $response = $this->actingAs($this->admin)
            ->getJson("/api/v1/admin/users/{$user->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.roles.0', 'customer');
    }

    public function test_admin_can_update_user_account_profile_and_role(): void
    {
        $user = User::factory()->create([
            'email' => 'before@example.com',
            'status' => UserStatus::ACTIVE->value,
        ]);
        $user->assignRole('customer');

        $response = $this->actingAs($this->admin)
            ->putJson("/api/v1/admin/users/{$user->id}", [
                'email' => 'after@example.com',
                'status' => UserStatus::SUSPENDED->value,
                'role' => 'seller_pending',
                'first_name' => 'Updated',
                'last_name' => 'User',
                'bio' => 'Managed by admin',
                'gender' => 'male',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.email', 'after@example.com')
            ->assertJsonPath('data.status', UserStatus::SUSPENDED->value)
            ->assertJsonPath('data.roles.0', 'seller_pending')
            ->assertJsonPath('data.profile.first_name', 'Updated');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => 'after@example.com',
            'status' => UserStatus::SUSPENDED->value,
        ]);

        $this->assertDatabaseHas('profiles', [
            'user_id' => $user->id,
            'first_name' => 'Updated',
            'last_name' => 'User',
            'bio' => 'Managed by admin',
        ]);
    }

    public function test_admin_can_update_user_status(): void
    {
        $user = User::factory()->create([
            'status' => UserStatus::ACTIVE->value,
        ]);
        $user->assignRole('customer');

        $response = $this->actingAs($this->admin)
            ->patchJson("/api/v1/admin/users/{$user->id}/status", [
                'status' => UserStatus::SUSPENDED->value,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', UserStatus::SUSPENDED->value);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'status' => UserStatus::SUSPENDED->value,
        ]);
    }

    public function test_admin_can_delete_user(): void
    {
        $user = User::factory()->create();
        $user->assignRole('customer');

        $response = $this->actingAs($this->admin)
            ->deleteJson("/api/v1/admin/users/{$user->id}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('users', [
            'id' => $user->id,
        ]);
    }

    public function test_admin_can_view_user_statistics(): void
    {
        $customer = User::factory()->create(['status' => UserStatus::ACTIVE->value]);
        $customer->assignRole('customer');

        $seller = User::factory()->create(['status' => UserStatus::SUSPENDED->value]);
        $seller->assignRole('seller');

        $pendingSeller = User::factory()->create(['status' => UserStatus::ACTIVE->value]);
        $pendingSeller->assignRole('seller_pending');

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/users/statistics');

        $response->assertStatus(200)
            ->assertJsonPath('data.admins', 1)
            ->assertJsonPath('data.customers', 1)
            ->assertJsonPath('data.sellers', 1)
            ->assertJsonPath('data.pending_sellers', 1);
    }

    public function test_non_admin_cannot_access_user_management(): void
    {
        $user = User::factory()->create();
        $user->assignRole('customer');

        $response = $this->actingAs($user)
            ->getJson('/api/v1/admin/users');

        $response->assertStatus(403);
    }

    public function test_admin_cannot_delete_self(): void
    {
        $response = $this->actingAs($this->admin)
            ->deleteJson("/api/v1/admin/users/{$this->admin->id}");

        $response->assertStatus(400);
    }
}
