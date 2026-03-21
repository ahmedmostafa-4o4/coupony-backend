<?php

namespace Tests\Unit;

use App\Domain\Notification\Models\Notification;
use App\Domain\Notification\Services\NotificationService;
use App\Domain\User\Enums\OtpChannels;
use App\Domain\User\Models\Otp;
use App\Domain\User\Models\User;
use App\Domain\User\Services\OtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class OtpServiceTest extends TestCase
{
    use RefreshDatabase;

    private OtpService $otpService;
    private MockInterface $notificationService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->notificationService = Mockery::mock(NotificationService::class);
        $this->otpService = new OtpService($this->notificationService);
    }

    public function test_generate_and_send_creates_otp_record()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
        ]);

        $notification = Notification::create([
            'user_id' => $user->id,
            'type' => 'otp_email',
            'title' => 'Test',
            'message' => 'Test',
            'channel' => 'email',
            'status' => 'pending',
        ]);

        $this->notificationService->shouldReceive('send')
            ->once()
            ->andReturn($notification);

        $otp = $this->otpService->generateAndSend(
            user: $user,
            purpose: 'verify_email',
            channel: 'email'
        );

        $this->assertInstanceOf(Otp::class, $otp);
        $this->assertEquals($user->id, $otp->user_id);
        $this->assertEquals('verify_email', $otp->purpose);
        $this->assertEquals('email', $otp->channel);
        $this->assertEquals('pending', $otp->status);
    }

    public function test_verify_succeeds_with_correct_code()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'email_verified_at' => null,
        ]);

        config(['otp.test_code' => '123456']);

        $notification = Notification::create([
            'user_id' => $user->id,
            'type' => 'otp_email',
            'title' => 'Test',
            'message' => 'Test',
            'channel' => 'email',
            'status' => 'pending',
        ]);

        $this->notificationService->shouldReceive('send')->andReturn($notification);

        $otp = $this->otpService->generateAndSend($user, 'verify_email', 'email');

        $result = $this->otpService->verify($user, '123456', 'verify_email', 'email');

        $this->assertTrue($result['success']);
        $this->assertEquals('OTP verified successfully.', $result['message']);
    }

    public function test_verify_fails_with_incorrect_code()
    {
        $user = User::factory()->create();

        config(['otp.test_code' => '123456']);

        $notification = Notification::create([
            'user_id' => $user->id,
            'type' => 'otp_email',
            'title' => 'Test',
            'message' => 'Test',
            'channel' => 'email',
            'status' => 'pending',
        ]);

        $this->notificationService->shouldReceive('send')->andReturn($notification);

        $this->otpService->generateAndSend($user, 'verify_email', 'email');

        $result = $this->otpService->verify($user, '999999', 'verify_email', 'email');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Invalid OTP code', $result['message']);
    }

    public function test_verify_fails_with_expired_otp()
    {
        $user = User::factory()->create();

        $otp = Otp::create([
            'user_id' => $user->id,
            'phone_or_email' => $user->email,
            'otp_hash' => hash('sha256', '123456'),
            'purpose' => 'verify_email',
            'channel' => 'email',
            'status' => 'pending',
            'attempts' => 0,
            'max_attempts' => 3,
            'expires_at' => now()->subMinutes(5),
        ]);

        $result = $this->otpService->verify($user, '123456', 'verify_email', 'email');

        $this->assertFalse($result['success']);
        $this->assertEquals('OTP_EXPIRED', $result['error_code']);
    }

    public function test_resend_enforces_rate_limiting()
    {
        $user = User::factory()->create();

        $notification = Notification::create([
            'user_id' => $user->id,
            'type' => 'otp_email',
            'title' => 'Test',
            'message' => 'Test',
            'channel' => 'email',
            'status' => 'pending',
        ]);

        $this->notificationService->shouldReceive('send')->andReturn($notification);

        $this->otpService->generateAndSend($user, 'verify_email', 'email');

        $result = $this->otpService->resend($user, 'verify_email', 'email');

        $this->assertFalse($result['success']);
        $this->assertEquals('RATE_LIMIT', $result['error_code']);
    }

    public function test_mask_recipient_masks_email_correctly()
    {
        $masked = $this->otpService->maskRecipient('test@example.com', OtpChannels::EMAIL->value);

        $this->assertStringStartsWith('te', $masked);
        $this->assertStringContainsString('*', $masked);
        $this->assertNotEquals('test@example.com', $masked);
    }

    public function test_mask_recipient_masks_phone_correctly()
    {
        $masked = $this->otpService->maskRecipient('1234567890', OtpChannels::SMS->value);

        $this->assertStringStartsWith('1234', $masked);
        $this->assertStringContainsString('*', $masked);
        $this->assertStringEndsWith('90', $masked);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
