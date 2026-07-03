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
}
