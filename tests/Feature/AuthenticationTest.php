<?php

namespace Tests\Feature;

use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'customer', 'guard_name' => 'sanctum']);
        Role::create(['name' => 'seller', 'guard_name' => 'sanctum']);
        Role::create(['name' => 'seller_pending', 'guard_name' => 'sanctum']);
        Role::create(['name' => 'admin', 'guard_name' => 'sanctum']);

        Storage::fake('public');
    }

    public function test_user_can_login_with_valid_credentials()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password_hash' => bcrypt('password123'),
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $user->assignRole('customer');

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
            'device_name' => 'Test Device',
            'role' => 'customer',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
            'message',
            'data' => [
                'user',
                'access_token',
                'refresh_token',
                'token_type',
                'expires_in',
            ],
        ]);
    }

    public function test_user_with_only_customer_role_cannot_login_as_seller()
    {
        $user = User::factory()->create([
            'email' => 'customer-only@example.com',
            'password_hash' => bcrypt('password123'),
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $user->assignRole('customer');

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'customer-only@example.com',
            'password' => 'password123',
            'role' => 'seller',
        ]);

        $response->assertStatus(401)
            ->assertJsonFragment(['message' => __('api.common.unauthorized')])
            ->assertJsonValidationErrors(['role']);
    }

    public function test_user_with_seller_pending_role_can_login_as_seller()
    {
        $user = User::factory()->create([
            'email' => 'seller-pending@example.com',
            'password_hash' => bcrypt('password123'),
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $user->assignRole('customer');
        $user->assignRole('seller_pending');

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'seller-pending@example.com',
            'password' => 'password123',
            'role' => 'seller',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.role', 'seller');
    }

    public function test_user_with_seller_role_can_login_as_seller()
    {
        $user = User::factory()->create([
            'email' => 'seller@example.com',
            'password_hash' => bcrypt('password123'),
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $user->assignRole('customer');
        $user->assignRole('seller');

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'seller@example.com',
            'password' => 'password123',
            'role' => 'seller',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.role', 'seller');
    }

    public function test_user_cannot_login_with_invalid_credentials()
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password_hash' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
            'role' => 'customer',
        ]);

        $response->assertStatus(401)
            ->assertJsonStructure(['message', 'errors']);
    }

    public function test_user_can_logout()
    {
        $user = User::factory()->create([
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/auth/logout');

        $response->assertStatus(200)
            ->assertJson(['message' => __('api.auth.logout_successful')]);
    }

    public function test_user_can_get_profile()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
        ]);

        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/auth/me');

        $response->assertStatus(200)
            ->assertJsonStructure(['data']);
    }

    public function test_user_can_change_password(): void
    {
        $user = User::factory()->create([
            'password_hash' => bcrypt('Ax7!mQ2#Lp9$'),
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/auth/change-password', [
                'current_password' => 'Ax7!mQ2#Lp9$',
                'password' => 'Rt4@kN8!Vz2%',
                'password_confirmation' => 'Rt4@kN8!Vz2%',
            ]);

        $response->assertOk()
            ->assertJsonFragment(['message' => __('api.auth.password_changed')]);

        $this->assertTrue(Hash::check('Rt4@kN8!Vz2%', $user->fresh()->password_hash));
    }

    public function test_user_cannot_change_password_with_incorrect_current_password(): void
    {
        $user = User::factory()->create([
            'password_hash' => bcrypt('Ax7!mQ2#Lp9$'),
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/auth/change-password', [
                'current_password' => 'Wr5!zT3#By8@',
                'password' => 'Rt4@kN8!Vz2%',
                'password_confirmation' => 'Rt4@kN8!Vz2%',
            ]);

        $response->assertStatus(401)
            ->assertJsonFragment(['message' => __('api.auth.invalid_credentials')]);
    }

    public function test_user_cannot_reuse_the_current_password_when_changing_password(): void
    {
        $user = User::factory()->create([
            'password_hash' => bcrypt('Ax7!mQ2#Lp9$'),
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/auth/change-password', [
                'current_password' => 'Ax7!mQ2#Lp9$',
                'password' => 'Ax7!mQ2#Lp9$',
                'password_confirmation' => 'Ax7!mQ2#Lp9$',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_user_can_update_own_profile_via_me_endpoint()
    {
        $user = User::factory()->create([
            'email' => 'before@example.com',
            'language' => 'ar',
        ]);

        $avatar = UploadedFile::fake()->create('avatar.jpg', 120, 'image/jpeg');

        $response = $this->actingAs($user, 'sanctum')
            ->withHeader('Accept', 'application/json')
            ->call(
                'PATCH',
                '/api/v1/auth/me',
                [
                    'email' => 'after@example.com',
                    'language' => 'en',
                    'first_name' => 'Updated',
                    'last_name' => 'Name',
                    'bio' => 'Updated from me endpoint',
                    'gender' => 'male',
                ],
                [],
                ['avatar' => $avatar]
            );

        $response->assertStatus(200)
            ->assertJsonPath('data.email', 'after@example.com')
            ->assertJsonPath('data.language', 'en')
            ->assertJsonPath('data.profile.first_name', 'Updated')
            ->assertJsonPath('data.profile.last_name', 'Name')
            ->assertJsonPath('data.profile.bio', 'Updated from me endpoint');

        $avatarUrl = $response->json('data.profile.avatar');
        $avatarPath = str_replace(Storage::disk('public')->url(''), '', $avatarUrl);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => 'after@example.com',
            'language' => 'en',
        ]);

        $this->assertDatabaseHas('profiles', [
            'user_id' => $user->id,
            'first_name' => 'Updated',
            'last_name' => 'Name',
            'bio' => 'Updated from me endpoint',
        ]);

        Storage::disk('public')->assertExists(ltrim($avatarPath, '/'));
    }

    public function test_me_update_requires_unique_email()
    {
        $user = User::factory()->create([
            'email' => 'owner@example.com',
        ]);

        User::factory()->create([
            'email' => 'taken@example.com',
        ]);

        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->patchJson('/api/v1/auth/me', [
                'email' => 'taken@example.com',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_user_can_remove_avatar_and_return_to_default_one()
    {
        $user = User::factory()->create();

        $avatar = UploadedFile::fake()->create('avatar.jpg', 120, 'image/jpeg');

        $uploadResponse = $this->actingAs($user, 'sanctum')
            ->withHeader('Accept', 'application/json')
            ->call(
                'PATCH',
                '/api/v1/auth/me',
                [],
                [],
                ['avatar' => $avatar]
            );

        $uploadResponse->assertStatus(200);

        $uploadedAvatarUrl = $uploadResponse->json('data.profile.avatar');
        $uploadedAvatarPath = ltrim(str_replace(Storage::disk('public')->url(''), '', $uploadedAvatarUrl), '/');

        Storage::disk('public')->assertExists($uploadedAvatarPath);

        $removeResponse = $this->actingAs($user, 'sanctum')
            ->patchJson('/api/v1/auth/me', [
                'remove_avatar' => true,
            ]);

        $removeResponse->assertStatus(200)
            ->assertJsonPath('data.profile.avatar', config('app.url') . '/users/avatars/default.svg');

        $this->assertDatabaseHas('profiles', [
            'user_id' => $user->id,
            'avatar_url' => config('app.url') . '/users/avatars/default.svg',
        ]);

        Storage::disk('public')->assertMissing($uploadedAvatarPath);
    }

    public function test_user_can_refresh_token()
    {
        $user = User::factory()->create([
            'status' => 'active',
        ]);

        $refreshToken = bin2hex(random_bytes(32));
        $user->update(['remember_token' => hash('sha256', $refreshToken)]);

        $response = $this->postJson('/api/v1/auth/refresh', [
            'refresh_token' => $refreshToken,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
            'message',
            'data' => [
                'access_token',
                'refresh_token',
                'token_type',
                'expires_in',
            ],
        ]);
    }

    public function test_suspended_user_cannot_login()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password_hash' => bcrypt('password123'),
            'status' => 'suspended',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
            'role' => 'customer',
        ]);

        $response->assertStatus(401)
            ->assertJsonFragment(['message' => __('api.auth.account_suspended')]);
    }

    public function test_unknown_email_returns_invalid_credentials_response()
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'missing@example.com',
            'password' => 'password123',
            'role' => 'customer',
        ]);

        $response->assertStatus(401)
            ->assertJsonFragment(['message' => __('api.auth.invalid_credentials')]);
    }

    public function test_user_can_login_with_google_and_create_a_new_customer_account()
    {
        config()->set('services.google.client_id', 'google-client-id');

        Http::fake([
            'https://oauth2.googleapis.com/tokeninfo*' => Http::response([
                'sub' => 'google-user-123',
                'email' => 'google-user@example.com',
                'email_verified' => 'true',
                'given_name' => 'Google',
                'family_name' => 'User',
                'aud' => 'google-client-id',
            ], 200),
        ]);

        $response = $this->postJson('/api/v1/auth/google', [
            'id_token' => 'header.payload.signature',
            'role' => 'customer',
            'device_name' => 'Pixel',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.user.email', 'google-user@example.com')
            ->assertJsonPath('data.role', 'customer')
            ->assertJsonStructure([
                'message',
                'data' => [
                    'user',
                    'session',
                    'access_token',
                    'refresh_token',
                    'token_type',
                    'expires_in',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'google-user@example.com',
            'provider' => 'google',
            'provider_id' => 'google-user-123',
        ]);
    }

    public function test_google_login_as_seller_first_time_does_not_assign_seller_pending_and_is_unauthorized()
    {
        config()->set('services.google.client_id', 'google-client-id');

        Http::fake([
            'https://oauth2.googleapis.com/tokeninfo*' => Http::response([
                'sub' => 'google-seller-123',
                'email' => 'google-seller@example.com',
                'email_verified' => 'true',
                'given_name' => 'Google',
                'family_name' => 'Seller',
                'aud' => 'google-client-id',
            ], 200),
        ]);

        $response = $this->postJson('/api/v1/auth/google', [
            'id_token' => 'header.payload.signature',
            'role' => 'seller',
        ]);

        $response->assertStatus(401)
            ->assertJsonFragment(['message' => __('api.common.unauthorized')])
            ->assertJsonValidationErrors(['role']);

        $user = User::where('email', 'google-seller@example.com')->firstOrFail();
        $this->assertTrue($user->hasRole('customer'));
        $this->assertFalse($user->hasRole('seller_pending'));
        $this->assertFalse($user->hasRole('seller'));
    }

    public function test_existing_user_is_linked_to_google_by_email()
    {
        config()->set('services.google.client_id', 'google-client-id');

        $user = User::factory()->create([
            'email' => 'existing@example.com',
            'provider' => null,
            'provider_id' => null,
            'email_verified_at' => null,
        ]);

        $user->assignRole('customer');

        Http::fake([
            'https://oauth2.googleapis.com/tokeninfo*' => Http::response([
                'sub' => 'google-existing-456',
                'email' => 'existing@example.com',
                'email_verified' => true,
                'given_name' => 'Existing',
                'family_name' => 'User',
                'aud' => 'google-client-id',
            ], 200),
        ]);

        $response = $this->postJson('/api/v1/auth/google', [
            'id_token' => 'header.payload.signature',
            'role' => 'customer',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.user.email', 'existing@example.com');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'provider' => 'google',
            'provider_id' => 'google-existing-456',
        ]);

        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_google_login_as_seller_is_allowed_for_user_with_seller_pending()
    {
        config()->set('services.google.client_id', 'google-client-id');

        $user = User::factory()->create([
            'email' => 'pending-google@example.com',
            'provider' => 'google',
            'provider_id' => 'google-pending-456',
            'email_verified_at' => now(),
        ]);
        $user->assignRole('customer');
        $user->assignRole('seller_pending');

        Http::fake([
            'https://oauth2.googleapis.com/tokeninfo*' => Http::response([
                'sub' => 'google-pending-456',
                'email' => 'pending-google@example.com',
                'email_verified' => true,
                'given_name' => 'Pending',
                'family_name' => 'Seller',
                'aud' => 'google-client-id',
            ], 200),
        ]);

        $response = $this->postJson('/api/v1/auth/google', [
            'id_token' => 'header.payload.signature',
            'role' => 'seller',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.role', 'seller');
    }

    public function test_google_login_as_seller_is_allowed_for_user_with_seller_role()
    {
        config()->set('services.google.client_id', 'google-client-id');

        $user = User::factory()->create([
            'email' => 'approved-google@example.com',
            'provider' => 'google',
            'provider_id' => 'google-seller-789',
            'email_verified_at' => now(),
        ]);
        $user->assignRole('customer');
        $user->assignRole('seller');

        Http::fake([
            'https://oauth2.googleapis.com/tokeninfo*' => Http::response([
                'sub' => 'google-seller-789',
                'email' => 'approved-google@example.com',
                'email_verified' => true,
                'given_name' => 'Approved',
                'family_name' => 'Seller',
                'aud' => 'google-client-id',
            ], 200),
        ]);

        $response = $this->postJson('/api/v1/auth/google', [
            'id_token' => 'header.payload.signature',
            'role' => 'seller',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.role', 'seller');
    }

    public function test_user_cannot_login_with_invalid_google_token()
    {
        Http::fake([
            'https://oauth2.googleapis.com/tokeninfo*' => Http::response([
                'error' => 'invalid_token',
            ], 400),
        ]);

        $response = $this->postJson('/api/v1/auth/google', [
            'id_token' => 'header.payload.signature',
            'role' => 'customer',
        ]);

        $response->assertStatus(401)
            ->assertJsonFragment(['message' => __('api.auth.invalid_credentials')]);
    }

    public function test_user_cannot_login_with_malformed_google_token()
    {
        Http::fake();

        $response = $this->postJson('/api/v1/auth/google', [
            'id_token' => '{"sub":"google-user-123"}',
            'role' => 'customer',
        ]);

        $response->assertStatus(401)
            ->assertJsonFragment(['message' => __('api.auth.invalid_credentials')]);

        Http::assertNothingSent();
    }

    public function test_customer_can_delete_own_account_with_current_password()
    {
        $user = User::factory()->create([
            'password_hash' => bcrypt('password123'),
            'provider' => null,
        ]);
        $user->assignRole('customer');

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/v1/auth/me', [
                'current_password' => 'password123',
            ]);

        $response->assertOk()
            ->assertJsonFragment(['message' => __('api.admin.users.deleted')]);

        $this->assertDatabaseMissing('users', [
            'id' => $user->id,
        ]);
    }

    public function test_customer_delete_own_account_requires_current_password_for_password_accounts()
    {
        $user = User::factory()->create([
            'password_hash' => bcrypt('password123'),
            'provider' => null,
        ]);
        $user->assignRole('customer');

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/v1/auth/me');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['current_password']);
    }

    public function test_seller_cannot_delete_account_via_customer_delete_endpoint()
    {
        $user = User::factory()->create();
        $user->assignRole('seller');

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/v1/auth/me', [
                'current_password' => 'password123',
            ]);

        $response->assertStatus(403)
            ->assertJsonFragment(['message' => __('api.common.unauthorized')]);
    }
}
