<?php

namespace Tests\Feature;

use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            ->assertJson(['message' => 'Logged out successfully']);
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
            ->patchJson('/api/v1/me', [
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
            ->assertJsonFragment(['message' => 'Invalid credentials']);
    }
}
