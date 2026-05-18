<?php

namespace Tests\Feature;

use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'customer', 'guard_name' => 'sanctum']);
        Role::create(['name' => 'seller', 'guard_name' => 'sanctum']);
        Role::create(['name' => 'seller_pending', 'guard_name' => 'sanctum']);
        Role::create(['name' => 'admin', 'guard_name' => 'sanctum']);
    }

    public function test_user_can_register_with_valid_data()
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'customer',
            'device_name' => 'Test Device',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'expires_at',
                    'expires_in_minutes',
                    'channel',
                    'masked_recipient',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
        ]);
    }

    public function test_registration_persists_phone_number()
    {
        $this->postJson('/api/v1/auth/register', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john-phone@example.com',
            'phone_number' => '+201234567890',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'customer',
        ])->assertStatus(201);

        $this->assertDatabaseHas('users', [
            'email' => 'john-phone@example.com',
            'phone_number' => '+201234567890',
        ]);
    }

    public function test_seller_registration_assigns_seller_pending_role()
    {
        $this->postJson('/api/v1/auth/register', [
            'first_name' => 'Seller',
            'last_name' => 'User',
            'email' => 'seller@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'seller',
        ])->assertStatus(201);

        $user = User::where('email', 'seller@example.com')->first();

        $this->assertFalse($user->hasRole('customer'));
        $this->assertTrue($user->hasRole('seller_pending'));
        $this->assertFalse($user->hasRole('seller'));
    }

    public function test_public_registration_rejects_admin_role()
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'first_name' => 'Admin',
            'last_name' => 'User',
            'email' => 'admin-public@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'admin',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['role']);
    }

    public function test_registration_requires_first_name()
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'customer',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['first_name']);
    }

    public function test_registration_requires_valid_email()
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'invalid-email',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'customer',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_registration_requires_password_confirmation()
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different',
            'role' => 'customer',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_registration_prevents_duplicate_email()
    {
        User::factory()->create([
            'email' => 'existing@example.com',
        ]);

        $response = $this->postJson('/api/v1/auth/register', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'existing@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'customer',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_registration_creates_user_profile()
    {
        $this->postJson('/api/v1/auth/register', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'customer',
        ]);

        $user = User::where('email', 'john@example.com')->first();

        $this->assertNotNull($user->profile);
        $this->assertEquals('John', $user->profile->first_name);
        $this->assertEquals('Doe', $user->profile->last_name);
    }

    public function test_registration_assigns_customer_role()
    {
        $this->postJson('/api/v1/auth/register', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'customer',
        ]);

        $user = User::where('email', 'john@example.com')->first();

        $this->assertTrue($user->hasRole('customer'));
    }
}
