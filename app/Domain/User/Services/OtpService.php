<?php

namespace App\Domain\User\Services;

use App\Domain\User\Enums\OtpChannels;
use App\Domain\User\Models\Otp;
use App\Domain\User\Models\User;
use App\Domain\User\Events\OtpGenerated;
use App\Domain\Notification\Services\NotificationService;
use Illuminate\Support\Facades\Log;

class OtpService
{
    public function __construct(
        private NotificationService $notificationService
    ) {
    }

    /**
     * Generate and send OTP to user.
     */
    public function generateAndSend(
        User $user,
        string $purpose,
        string $channel = 'email',
        ?string $customRecipient = null
    ): Otp {
        // Invalidate previous OTPs for this purpose
        $this->invalidatePreviousOtps($user, $purpose, $channel);

        // Generate OTP code
        $code = $this->generateCode();

        // Determine recipient
        $recipient = $customRecipient ?? $this->getRecipient($user, $channel);

        // Create OTP record
        $otp = Otp::create([
            'user_id' => $user->id,
            'phone_or_email' => $recipient,
            'otp_hash' => hash('sha256', $code),
            'purpose' => $purpose,
            'channel' => $channel,
            'status' => 'pending',
            'attempts' => 0,
            'max_attempts' => config('otp.max_attempts', 3),
            'expires_at' => now()->addMinutes((int) config('otp.expiry_minutes', 10)),
        ]);

        // Send OTP via appropriate channel
        $this->sendOtp($otp, $code, $user);

        // Dispatch event
        event(new OtpGenerated($otp, $user, $code));

        return $otp;
    }

    /**
     * Verify OTP code.
     */
    public function verify(
        User $user,
        string $code,
        string $purpose,
        string $channel = 'email'
    ): array {
        // Find the OTP
        $otp = Otp::where('user_id', $user->id)
            ->where('purpose', $purpose)
            ->where('channel', $channel)
            ->where('status', 'pending')
            ->latest()
            ->first();

        if (!$otp) {
            return [
                'success' => false,
                'message' => 'No valid OTP found. Please request a new one.',
                'error_code' => 'OTP_NOT_FOUND',
            ];
        }

        if ($otp->isExpired()) {
            $otp->markAsExpired();
            return [
                'success' => false,
                'message' => 'OTP has expired. Please request a new one.',
                'error_code' => 'OTP_EXPIRED',
            ];
        }

        if ($otp->isBlocked()) {
            return [
                'success' => false,
                'message' => 'Too many failed attempts. Please request a new OTP.',
                'error_code' => 'OTP_BLOCKED',
                'retry_after' => $otp->expires_at->diffInMinutes(now()),
            ];
        }

        // Verify the code
        if ($otp->verify($code)) {
            // Execute post-verification actions
            $this->handleSuccessfulVerification($user, $purpose);

            return [
                'success' => true,
                'message' => 'OTP verified successfully.',
            ];
        }

        $remainingAttempts = $otp->max_attempts - $otp->attempts;

        return [
            'success' => false,
            'message' => "Invalid OTP code. {$remainingAttempts} attempts remaining.",
            'error_code' => 'OTP_INVALID',
            'remaining_attempts' => $remainingAttempts,
        ];
    }

    /**
     * Resend OTP.
     */
    public function resend(
        User $user,
        string $purpose,
        string $channel = 'email'
    ): array {
        // Check rate limiting
        $recentOtp = Otp::where('user_id', $user->id)
            ->where('purpose', $purpose)
            ->where('channel', $channel)
            ->where('created_at', '>', now()->subMinutes(1))
            ->first();

        if ($recentOtp) {
            $retryAfter = 60 - $recentOtp->created_at->diffInSeconds(now());
            return [
                'success' => false,
                'message' => "Please wait {$retryAfter} seconds before requesting a new OTP.",
                'error_code' => 'RATE_LIMIT',
                'retry_after' => $retryAfter,
            ];
        }

        // Generate new OTP
        $otp = $this->generateAndSend($user, $purpose, $channel);

        return [
            'success' => true,
            'message' => 'OTP sent successfully.',
            'expires_at' => $otp->expires_at->toIso8601String(),
        ];
    }

    public function maskRecipient(string $recipient, string $channel): string
    {
        if ($channel === OtpChannels::EMAIL) {
            [$name, $domain] = explode('@', $recipient);
            $maskedName = substr($name, 0, 2) . str_repeat('*', strlen($name) - 2);
            return $maskedName . '@' . $domain;
        }

        // Phone masking
        return substr($recipient, 0, 4) . str_repeat('*', strlen($recipient) - 6) . substr($recipient, -2);
    }

    /**
     * Generate random OTP code.
     */
    private function generateCode(int $length = 6): string
    {
        if (app()->environment('local', 'testing')) {
            return config('otp.test_code', '123456');
        }

        return str_pad(
            (string) random_int(0, (10 ** $length) - 1),
            $length,
            '0',
            STR_PAD_LEFT
        );
    }

    /**
     * Send OTP via channel.
     */
    private function sendOtp(Otp $otp, string $code, User $user): void
    {
        match ($otp->channel) {
            'email' => $this->sendViaEmail($otp, $code, $user),
            'sms' => $this->sendViaSms($otp, $code, $user),
            'whatsapp' => $this->sendViaWhatsApp($otp, $code, $user),
            default => throw new \Exception("Unsupported OTP channel: {$otp->channel}"),
        };
    }

    /**
     * Send OTP via email.
     */
    private function sendViaEmail(Otp $otp, string $code, User $user): void
    {
        $this->notificationService->send(
            user: $user,
            type: 'otp_email',
            title: $this->getEmailSubject($otp->purpose),
            message: $this->getEmailMessage($code, $otp->purpose),
            channel: 'email',
            data: [
                'code' => $code,
                'purpose' => $otp->purpose,
                'expires_at' => $otp->expires_at->format('H:i'),
                'expires_in_minutes' => $otp->expires_at->diffInMinutes(now()),
            ]
        );
    }

    /**
     * Send OTP via SMS.
     */
    private function sendViaSms(Otp $otp, string $code, User $user): void
    {
        $message = $this->getSmsMessage($code, $otp->purpose);

        $this->notificationService->send(
            user: $user,
            type: 'otp_sms',
            title: 'Verification Code',
            message: $message,
            channel: 'sms',
            data: ['code' => $code, 'purpose' => $otp->purpose]
        );
    }

    /**
     * Send OTP via WhatsApp.
     */
    private function sendViaWhatsApp(Otp $otp, string $code, User $user): void
    {
        $message = $this->getWhatsAppMessage($code, $otp->purpose);

        // Implementation depends on WhatsApp Business API
        // This is a placeholder
        Log::info("WhatsApp OTP: {$code} to {$user->phone_number}");
    }

    /**
     * Get recipient based on channel.
     */
    private function getRecipient(User $user, string $channel): string
    {
        return match ($channel) {
            'email' => $user->email,
            'sms', 'whatsapp' => $user->phone_number,
            default => throw new \Exception("Invalid channel: {$channel}"),
        };
    }

    /**
     * Invalidate previous OTPs.
     */
    private function invalidatePreviousOtps(User $user, string $purpose, string $channel): void
    {
        Otp::where('user_id', $user->id)
            ->where('purpose', $purpose)
            ->where('channel', $channel)
            ->whereIn('status', ['pending'])
            ->update(['status' => 'expired']);
    }

    /**
     * Handle successful verification.
     */
    private function handleSuccessfulVerification(User $user, string $purpose): void
    {
        match ($purpose) {
            'verify_email' => $user->markEmailAsVerified(),
            'verify_phone' => $user->markPhoneAsVerified(),
            'login' => null, // Login handled separately
            'reset_password' => null, // Password reset handled separately
            default => null,
        };
    }

    /**
     * Get email subject based on purpose.
     */
    private function getEmailSubject(string $purpose): string
    {
        return match ($purpose) {
            'verify_email' => 'Verify Your Email',
            'verify_phone' => 'Verify Your Phone Number',
            'login' => 'Login Verification Code',
            'reset_password' => 'Password Reset Code',
            default => 'Verification Code',
        };
    }

    /**
     * Get email message.
     */
    private function getEmailMessage(string $code, string $purpose): string
    {
        $action = match ($purpose) {
            'verify_email' => 'verify your email address',
            'verify_phone' => 'verify your phone number',
            'login' => 'complete your login',
            'reset_password' => 'reset your password',
            default => 'verify your account',
        };

        return "Your verification code is: {$code}. Use this code to {$action}. This code will expire in " . config('otp.expiry_minutes', 10) . " minutes.";
    }

    /**
     * Get SMS message.
     */
    private function getSmsMessage(string $code, string $purpose): string
    {
        $appName = config('app.name');
        return "{$appName}: Your verification code is {$code}. Valid for " . config('otp.expiry_minutes', 10) . " minutes.";
    }

    /**
     * Get WhatsApp message.
     */
    private function getWhatsAppMessage(string $code, string $purpose): string
    {
        return $this->getSmsMessage($code, $purpose);
    }


}