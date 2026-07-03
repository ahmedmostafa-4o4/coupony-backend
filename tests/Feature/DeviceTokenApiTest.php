<?php

namespace Tests\Feature;

use App\Domain\User\Models\User;
use App\Domain\User\Models\UserDeviceToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeviceTokenApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_has_active_device_tokens_relation(): void
    {
        $user = User::factory()->create();
        UserDeviceToken::factory()->create([
            'user_id' => $user->id,
            'token' => 'active-token',
            'revoked_at' => null,
        ]);
        UserDeviceToken::factory()->create([
            'user_id' => $user->id,
            'token' => 'revoked-token',
            'revoked_at' => now(),
        ]);

        $this->assertSame(
            ['active-token'],
            $user->deviceTokens()->active()->pluck('token')->all()
        );
    }

    public function test_authenticated_user_can_register_device_token(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/me/device-tokens', [
                'token' => 'fcm-token-1',
                'platform' => 'android',
                'device_id' => 'pixel-8',
                'app_version' => '1.2.3',
            ])
            ->assertOk()
            ->assertJsonPath('data.token', 'fcm-token-1')
            ->assertJsonPath('data.platform', 'android')
            ->assertJsonPath('data.device_id', 'pixel-8')
            ->assertJsonPath('data.app_version', '1.2.3');

        $this->assertDatabaseHas('user_device_tokens', [
            'user_id' => $user->id,
            'token' => 'fcm-token-1',
            'platform' => 'android',
            'device_id' => 'pixel-8',
            'app_version' => '1.2.3',
            'revoked_at' => null,
        ]);
    }

    public function test_registering_existing_token_reassigns_and_reactivates_it(): void
    {
        $oldUser = User::factory()->create();
        $newUser = User::factory()->create();
        UserDeviceToken::factory()->create([
            'user_id' => $oldUser->id,
            'token' => 'shared-token',
            'platform' => 'ios',
            'revoked_at' => now(),
        ]);

        $this->actingAs($newUser, 'sanctum')
            ->postJson('/api/v1/me/device-tokens', [
                'token' => 'shared-token',
                'platform' => 'android',
            ])
            ->assertOk();

        $this->assertDatabaseHas('user_device_tokens', [
            'user_id' => $newUser->id,
            'token' => 'shared-token',
            'platform' => 'android',
            'revoked_at' => null,
        ]);
    }

    public function test_authenticated_user_can_unregister_own_device_token(): void
    {
        $user = User::factory()->create();
        UserDeviceToken::factory()->create([
            'user_id' => $user->id,
            'token' => 'logout-token',
            'revoked_at' => null,
        ]);

        $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/v1/me/device-tokens', ['token' => 'logout-token'])
            ->assertNoContent();

        $this->assertNotNull(
            UserDeviceToken::query()->where('token', 'logout-token')->firstOrFail()->revoked_at
        );
    }

    public function test_user_cannot_unregister_another_users_device_token(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        UserDeviceToken::factory()->create([
            'user_id' => $owner->id,
            'token' => 'owned-token',
            'revoked_at' => null,
        ]);

        $this->actingAs($other, 'sanctum')
            ->deleteJson('/api/v1/me/device-tokens', ['token' => 'owned-token'])
            ->assertNoContent();

        $this->assertNull(
            UserDeviceToken::query()->where('token', 'owned-token')->firstOrFail()->revoked_at
        );
    }
}
