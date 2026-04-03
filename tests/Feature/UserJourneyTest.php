<?php

namespace Tests\Feature;

use App\Domain\Store\Models\StoreCategory;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserJourneyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'customer', 'guard_name' => 'sanctum']);
        Role::create(['name' => 'seller', 'guard_name' => 'sanctum']);
        Role::create(['name' => 'seller_pending', 'guard_name' => 'sanctum']);

        Storage::fake('public');
    }

    public function test_complete_customer_registration_and_login_journey()
    {
        // Step 1: Register a new customer
        $registerResponse = $this->postJson('/api/v1/auth/register', [
            'first_name' => 'Jane',
            'last_name' => 'Customer',
            'email' => 'jane@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'customer',
            'device_name' => 'Test Device',
        ]);

        $registerResponse->assertStatus(201);

        // Step 2: Verify email with OTP
        config(['otp.test_code' => '123456']);

        $verifyResponse = $this->postJson('/api/v1/auth/otp/verify', [
            'email' => 'jane@example.com',
            'code' => '123456',
            'purpose' => 'verify_email',
        ]);

        $verifyResponse->assertStatus(200)
            ->assertJsonStructure([
            'data' => [
                'access_token',
                'refresh_token',
            ],
        ]);

        $accessToken = $verifyResponse->json('data.access_token');

        // Step 3: Get user profile
        $profileResponse = $this->withHeader('Authorization', "Bearer {$accessToken}")
            ->getJson('/api/v1/auth/me');

        $profileResponse->assertStatus(200)
            ->assertJsonPath('data.email', 'jane@example.com');

        // Step 4: Logout
        $logoutResponse = $this->withHeader('Authorization', "Bearer {$accessToken}")
            ->postJson('/api/v1/auth/logout');

        $logoutResponse->assertStatus(200);

        // Step 5: Login again
        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => 'jane@example.com',
            'password' => 'password123',
            'device_name' => 'Test Device',
            'role' => 'customer',
        ]);

        $loginResponse->assertStatus(200)
            ->assertJsonStructure([
            'data' => [
                'access_token',
                'refresh_token',
            ],
        ]);
    }

    public function test_complete_seller_registration_and_store_creation_journey()
    {
        // Step 1: Register as seller
        $registerResponse = $this->postJson('/api/v1/auth/register', [
            'first_name' => 'John',
            'last_name' => 'Seller',
            'email' => 'john.seller@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'seller',
            'device_name' => 'Test Device',
        ]);

        $registerResponse->assertStatus(201);

        // Step 2: Verify email
        config(['otp.test_code' => '123456']);

        $verifyResponse = $this->postJson('/api/v1/auth/otp/verify', [
            'email' => 'john.seller@example.com',
            'code' => '123456',
            'purpose' => 'verify_email',
        ]);

        $verifyResponse->assertStatus(200);
        $accessToken = $verifyResponse->json('data.access_token');

        // Step 3: Login as seller
        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => 'john.seller@example.com',
            'password' => 'password123',
            'device_name' => 'Test Device',
            'role' => 'seller',
        ]);

        $loginResponse->assertStatus(200)
            ->assertJsonPath('data.role', 'seller')
            ->assertJsonPath('data.is_store_owner', false)
            ->assertJsonStructure([
            'data' => [
                'access_token',
                'refresh_token',
            ],
        ]);

        $newAccessToken = $loginResponse->json('data.access_token');

        // Step 4: Create store
        $category = StoreCategory::factory()->create();

        $storeResponse = $this->withHeader('Authorization', "Bearer {$newAccessToken}")
            ->postJson('/api/v1/stores', [
            'name' => 'John\'s Store',
            'description' => 'A great store',
            'phone' => '+1234567890',
            'address_line1' => '123 Business St',
            'city' => 'Commerce City',
            'latitude' => '40.7128',
            'longitude' => '-74.0060',
            'categories' => [$category->id],
            'verification_docs' => [
                'commercial_register' => UploadedFile::fake()->create('commercial.pdf', 1000),
                'tax_card' => UploadedFile::fake()->create('tax.pdf', 1000),
                'id_card_front' => UploadedFile::fake()->create('id_front.pdf', 1000),
                'id_card_back' => UploadedFile::fake()->create('id_back.pdf', 1000),
            ],
        ]);

        $storeResponse->assertStatus(201)
            ->assertJsonPath('message', 'Store created successfully. Pending approval.');

        // Step 5: Verify user now has seller_pending role
        $user = User::where('email', 'john.seller@example.com')->first();
        $this->assertTrue($user->hasRole('seller_pending'));
        $this->assertCount(1, $user->stores);
    }

    public function test_password_reset_journey()
    {
        // Step 1: Create user
        $user = User::factory()->create([
            'email' => 'reset@example.com',
            'password_hash' => bcrypt('oldpassword'),
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        // Step 2: Request password reset OTP
        $otpResponse = $this->postJson('/api/v1/auth/otp/send', [
            'email' => 'reset@example.com',
            'purpose' => 'reset_password',
        ]);

        $otpResponse->assertStatus(200);

        // Step 3: Verify OTP
        config(['otp.test_code' => '123456']);

        $verifyResponse = $this->postJson('/api/v1/auth/otp/verify', [
            'email' => 'reset@example.com',
            'code' => '123456',
            'purpose' => 'reset_password',
        ]);

        $verifyResponse->assertStatus(200)
            ->assertJsonPath('data.verified', true);
    }

    public function test_token_refresh_journey()
    {
        // Step 1: Create and login user
        $user = User::factory()->create([
            'email' => 'refresh@example.com',
            'password_hash' => bcrypt('password123'),
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $user->assignRole('customer');

        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => 'refresh@example.com',
            'password' => 'password123',
            'device_name' => 'Test Device',
            'role' => 'customer',
        ]);

        $loginResponse->assertStatus(200);
        $refreshToken = $loginResponse->json('data.refresh_token');

        // Step 2: Use refresh token to get new access token
        $refreshResponse = $this->postJson('/api/v1/auth/refresh', [
            'refresh_token' => $refreshToken,
        ]);

        $refreshResponse->assertStatus(200)
            ->assertJsonStructure([
            'data' => [
                'access_token',
                'refresh_token',
            ],
        ]);

        // Step 3: Verify new token works
        $newAccessToken = $refreshResponse->json('data.access_token');

        $profileResponse = $this->withHeader('Authorization', "Bearer {$newAccessToken}")
            ->getJson('/api/v1/auth/me');

        $profileResponse->assertStatus(200);
    }
}

