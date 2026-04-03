<?php

namespace Tests\Unit;

use App\Domain\User\Enums\OtpChannels;
use App\Domain\User\Enums\OtpPurposes;
use App\Domain\User\Models\User;
use App\Domain\User\Services\AuthenticationService;
use App\Domain\User\Services\OtpService;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\TestCase;

class AuthenticationServiceTest extends TestCase
{
    use RefreshDatabase;

    private AuthenticationService $authService;
    private Hasher $hasher;
    private OtpService $otpService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hasher = $this->app->make(Hasher::class);
        $this->otpService = Mockery::mock(OtpService::class);
        $this->authService = new AuthenticationService($this->hasher, $this->otpService);

        // Create roles
        \Spatie\Permission\Models\Role::create(['name' => 'customer', 'guard_name' => 'sanctum']);
        \Spatie\Permission\Models\Role::create(['name' => 'seller', 'guard_name' => 'sanctum']);
        \Spatie\Permission\Models\Role::create(['name' => 'admin', 'guard_name' => 'sanctum']);
    }

    public function test_login_with_valid_credentials()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password_hash' => $this->hasher->make('password123'),
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $user->assignRole('customer');

        $context = [
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Mozilla/5.0',
            'device_name' => 'Test Device',
            'role' => 'customer',
        ];

        $result = $this->authService->login('test@example.com', 'password123', $context);

        $this->assertArrayHasKey('access_token', $result);
        $this->assertArrayHasKey('refresh_token', $result);
        $this->assertArrayHasKey('user', $result);
        $this->assertEquals('Bearer', $result['token_type']);
    }

    public function test_login_fails_with_invalid_credentials()
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password_hash' => $this->hasher->make('password123'),
        ]);

        $this->expectException(ValidationException::class);

        $this->authService->login('test@example.com', 'wrongpassword', []);
    }

    public function test_login_fails_with_suspended_account()
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password_hash' => $this->hasher->make('password123'),
            'status' => 'suspended',
        ]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage(__('api.auth.account_suspended'));

        $this->authService->login('test@example.com', 'password123', []);
    }

    public function test_login_sends_otp_for_unverified_user()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password_hash' => $this->hasher->make('password123'),
            'status' => 'active',
            'email_verified_at' => null,
        ]);

        $otp = new \App\Domain\User\Models\Otp();
        $otp->expires_at = now()->addMinutes(10);

        $this->otpService->shouldReceive('generateAndSend')
            ->once()
            ->with(
            Mockery::on(fn($u) => $u->id === $user->id),
            OtpPurposes::VERIFY_EMAIL->value,
            OtpChannels::EMAIL->value
        )
            ->andReturn($otp);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage(__('api.auth.verification_code_sent'));

        $this->authService->login('test@example.com', 'password123', []);
    }

    public function test_logout_revokes_current_token()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        $this->authService->logout($user, $token->plainTextToken);

        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
        ]);
    }

    public function test_logout_all_revokes_all_tokens()
    {
        $user = User::factory()->create();
        $user->createToken('token1');
        $user->createToken('token2');

        $this->authService->logoutAll($user);

        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
        ]);
    }

    public function test_refresh_token_generates_new_tokens()
    {
        $user = User::factory()->create([
            'status' => 'active',
        ]);

        $refreshToken = bin2hex(random_bytes(32));
        $user->update(['remember_token' => hash('sha256', $refreshToken)]);

        $result = $this->authService->refreshToken($refreshToken);

        $this->assertArrayHasKey('access_token', $result);
        $this->assertArrayHasKey('refresh_token', $result);
        $this->assertNotEquals($refreshToken, $result['refresh_token']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
