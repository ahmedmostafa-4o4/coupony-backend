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

        $this->admin = User::factory()->create([
            'email' => 'admin.secure@example.com'
        ]);
        $this->admin->profile()->update([
            'first_name' => 'Admin',
            'last_name' => 'User',
            'gender' => 'male',
        ]);
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
    public function test_admin_can_revoke_all_user_sessions(): void
    {
        $user = User::factory()->create();
        $user->assignRole('customer');

        // Create a dummy token
        $user->createToken('test-token');

        // Verify token exists
        $this->assertDatabaseCount('personal_access_tokens', 1);

        $response = $this->actingAs($this->admin)
            ->deleteJson("/api/v1/admin/users/{$user->id}/sessions");

        $response->assertStatus(200)
            ->assertJsonPath('message', __('api.admin.users.sessions_revoked', [], 'en'));

        // Verify token is deleted
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_admin_can_revoke_specific_user_session(): void
    {
        $user = User::factory()->create();
        $user->assignRole('customer');

        $token = $user->createToken('test-token');

        $response = $this->actingAs($this->admin)
            ->deleteJson("/api/v1/admin/users/{$user->id}/sessions/{$token->accessToken->id}");

        $response->assertStatus(200)
            ->assertJsonPath('message', __('api.admin.users.session_revoked', [], 'en'));

        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $token->accessToken->id,
        ]);
    }

    public function test_admin_can_search_users_by_name_and_email(): void
    {
        // User 1
        $user1 = User::factory()->create(['email' => 'john.doe@example.com']);
        $user1->assignRole('customer');
        $user1->profile()->update(['first_name' => 'John', 'last_name' => 'Doe']);

        // User 2
        $user2 = User::factory()->create(['email' => 'jane.smith@example.com']);
        $user2->assignRole('customer');
        $user2->profile()->update(['first_name' => 'Jane', 'last_name' => 'Smith']);

        // User 3
        $user3 = User::factory()->create(['email' => 'someone@example.com']);
        $user3->assignRole('seller');
        $user3->profile()->update(['first_name' => 'John', 'last_name' => 'Wick']);

        // Search by email
        $response1 = $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/users?search=jane.smith');
        
        $response1->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.email', 'jane.smith@example.com');

        // Search by first name (Should match John Doe and John Wick)
        $response2 = $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/users?search=John');
        
        $response2->assertStatus(200)
            ->assertJsonCount(2, 'data');
            
        // Search by last name using 'search'
        $response3 = $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/users?search=Smith');
            
        $response3->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.profile.last_name', 'Smith');

        // Search by last name using 'q'
        $response4 = $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/users?q=Smith');
            
        $response4->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.profile.last_name', 'Smith');
    }

    public function test_admin_can_list_all_users_when_no_filters_applied(): void
    {
        // One admin already created in setUp

        // Create one customer
        $customer = User::factory()->create(['status' => UserStatus::ACTIVE->value]);
        $customer->assignRole('customer');

        // Create one seller
        $seller = User::factory()->create(['status' => UserStatus::ACTIVE->value]);
        $seller->assignRole('seller');

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/users');
            
        $response->assertStatus(200);

        // There should be 3 users: 1 admin (from setup), 1 customer, 1 seller
        $this->assertCount(3, $response->json('data'));
    }

    public function test_admin_can_paginate_users_with_camel_case_parameter(): void
    {
        // We have 1 admin from setup. Let's create 3 more users.
        User::factory()->count(3)->create(['status' => UserStatus::ACTIVE->value]);

        // Request with perPage=2 (camelCase)
        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/users?perPage=2');
            
        $response->assertStatus(200);

        // It should return exactly 2 items in data
        $this->assertCount(2, $response->json('data'));
        
        // Ensure meta.per_page reflects our setting
        $response->assertJsonPath('meta.per_page', 2);
    }

    public function test_admin_can_update_user_password(): void
    {
        $user = User::factory()->create();

        $newPassword = 'NewSecurePassword123!';

        // Pre-create a token to ensure it gets revoked
        $token = $user->createToken('test-token')->plainTextToken;
        $this->assertDatabaseCount('personal_access_tokens', 1);

        $response = $this->actingAs($this->admin)
            ->patchJson("/api/v1/admin/users/{$user->id}/password", [
                'password' => $newPassword,
                'password_confirmation' => $newPassword,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('message', __('api.admin.users.password_updated', [], 'en'));

        // Verify the password was hashed and saved correctly
        $user->refresh();
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check($newPassword, $user->password_hash));

        // Verify sessions/tokens were revoked
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_admin_can_create_a_new_user(): void
    {
        $payload = [
            'email' => 'new.seller@example.com',
            'phone_number' => '+1987654321',
            'password' => 'SecurePassword123!',
            'password_confirmation' => 'SecurePassword123!',
            'first_name' => 'New',
            'last_name' => 'Seller',
            'role' => 'seller',
            'status' => UserStatus::ACTIVE->value,
            'gender' => 'male',
        ];

        $response = $this->actingAs($this->admin)
            ->postJson("/api/v1/admin/users", $payload);

        $response->assertStatus(201)
            ->assertJsonPath('message', __('api.admin.users.created', [], 'en'))
            ->assertJsonPath('data.email', 'new.seller@example.com')
            ->assertJsonPath('data.roles.0', 'seller')
            ->assertJsonPath('data.profile.first_name', 'New');

        $this->assertDatabaseHas('users', [
            'email' => 'new.seller@example.com',
        ]);

        $this->assertDatabaseHas('profiles', [
            'first_name' => 'New',
            'last_name' => 'Seller',
            'gender' => 'male',
        ]);
    }
}
