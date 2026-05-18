<?php

namespace Tests\Feature;

use App\Domain\User\Models\Otp;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OtpTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_request_otp()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
        ]);

        $response = $this->postJson('/api/v1/auth/otp/send', [
            'email' => 'test@example.com',
            'purpose' => 'verify_email',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'expires_at',
                    'expires_in_minutes',
                    'channel',
                    'masked_recipient',
                ],
            ]);

        $this->assertDatabaseHas('otps', [
            'user_id' => $user->id,
            'purpose' => 'verify_email',
            'status' => 'pending',
        ]);
    }

    public function test_email_otp_response_masks_email_recipient_correctly()
    {
        User::factory()->create([
            'email' => 'tester@example.com',
        ]);

        $response = $this->postJson('/api/v1/auth/otp/send', [
            'email' => 'tester@example.com',
            'purpose' => 'verify_email',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.masked_recipient', 'te****@example.com');
    }

    public function test_phone_verification_defaults_to_sms_channel()
    {
        Http::fake();
        config([
            'services.twilio.account_sid' => 'test-sid',
            'services.twilio.auth_token' => 'test-token',
            'services.twilio.from_number' => '+10000000000',
        ]);

        $user = User::factory()->create([
            'phone_number' => '+201234567890',
        ]);

        $response = $this->postJson('/api/v1/auth/otp/send', [
            'phone_number' => '+201234567890',
            'purpose' => 'verify_phone',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.channel', 'sms');

        $this->assertDatabaseHas('otps', [
            'user_id' => $user->id,
            'purpose' => 'verify_phone',
            'channel' => 'sms',
            'status' => 'pending',
        ]);
    }

    public function test_user_can_verify_otp_with_correct_code()
    {
        config(['otp.test_code' => '123456']);

        $user = User::factory()->create([
            'email' => 'test@example.com',
            'email_verified_at' => null,
        ]);

        // Send OTP first
        $this->postJson('/api/v1/auth/otp/send', [
            'email' => 'test@example.com',
            'purpose' => 'verify_email',
        ]);

        // Verify OTP
        $response = $this->postJson('/api/v1/auth/otp/verify', [
            'email' => 'test@example.com',
            'code' => '123456',
            'purpose' => 'verify_email',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'verified',
                    'purpose',
                ],
            ]);
    }

    public function test_user_cannot_verify_otp_with_incorrect_code()
    {
        config(['otp.test_code' => '123456']);

        $user = User::factory()->create([
            'email' => 'test@example.com',
        ]);

        $this->postJson('/api/v1/auth/otp/send', [
            'email' => 'test@example.com',
            'purpose' => 'verify_email',
        ]);

        $response = $this->postJson('/api/v1/auth/otp/verify', [
            'email' => 'test@example.com',
            'code' => '999999',
            'purpose' => 'verify_email',
        ]);

        $response->assertStatus(422)
            ->assertJsonFragment(['error_code' => 'OTP_INVALID']);
    }

    public function test_verified_user_email_otp_verification_does_not_issue_tokens()
    {
        config(['otp.test_code' => '123456']);

        $user = User::factory()->create([
            'email' => 'verified@example.com',
            'email_verified_at' => now()->subDay(),
        ]);

        $originalVerifiedAt = $user->email_verified_at?->toIso8601String();

        $this->postJson('/api/v1/auth/otp/send', [
            'email' => 'verified@example.com',
            'purpose' => 'verify_email',
        ])->assertStatus(200);

        $response = $this->postJson('/api/v1/auth/otp/verify', [
            'email' => 'verified@example.com',
            'code' => '123456',
            'purpose' => 'verify_email',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.verified', true)
            ->assertJsonMissingPath('data.access_token')
            ->assertJsonMissingPath('data.refresh_token');

        $this->assertSame($originalVerifiedAt, $user->fresh()->email_verified_at?->toIso8601String());
    }

    public function test_user_can_resend_otp()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
        ]);

        // Create an old OTP
        // Create an expired OTP that's old enough to bypass rate limiting
        $otp = new Otp([
            'user_id' => $user->id,
            'phone_or_email' => $user->email,
            'otp_hash' => hash('sha256', '123456'),
            'purpose' => 'verify_email',
            'channel' => 'email',
            'status' => 'expired',
            'attempts' => 0,
            'max_attempts' => 3,
            'expires_at' => now()->subMinutes(10),
        ]);
        $otp->timestamps = false;
        $otp->created_at = now()->subMinutes(10);
        $otp->updated_at = now()->subMinutes(10);
        $otp->save();

        $response = $this->postJson('/api/v1/auth/otp/resend', [
            'email' => 'test@example.com',
            'purpose' => 'verify_email',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'expires_at',
                ],
            ]);
    }

    public function test_otp_resend_enforces_rate_limiting()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
        ]);

        // Send OTP
        $this->postJson('/api/v1/auth/otp/send', [
            'email' => 'test@example.com',
            'purpose' => 'verify_email',
        ]);

        // Try to resend immediately
        $response = $this->postJson('/api/v1/auth/otp/resend', [
            'email' => 'test@example.com',
            'purpose' => 'verify_email',
        ]);

        $response->assertStatus(429)
            ->assertJsonFragment(['error_code' => 'RATE_LIMIT']);
    }

    public function test_otp_send_requires_email_or_phone()
    {
        $response = $this->postJson('/api/v1/auth/otp/send', [
            'purpose' => 'verify_email',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_otp_verify_requires_code()
    {
        $response = $this->postJson('/api/v1/auth/otp/verify', [
            'email' => 'test@example.com',
            'purpose' => 'verify_email',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['code']);
    }
}
