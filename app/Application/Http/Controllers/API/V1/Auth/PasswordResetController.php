<?php

namespace App\Application\Http\Controllers\API\V1\Auth;

use App\Application\Http\Controllers\Controller;
use App\Application\Http\Requests\ForgotPasswordRequest;
use App\Application\Http\Requests\ResetPasswordRequest;
use App\Application\Http\Requests\VerifyPasswordResetOtpRequest;
use App\Domain\User\Enums\OtpChannels;
use App\Domain\User\Enums\OtpPurposes;
use App\Domain\User\Models\User;
use App\Domain\User\Services\OtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Throwable;

class PasswordResetController extends Controller
{
    public function __construct(
        private readonly OtpService $otpService
    ) {
    }

    /**
     * Send password reset OTP.
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        try {
            $user = User::where('email', $request->input('email'))->first();

            if (!$user) {
                // Don't reveal if user exists for security
                return response()->json([
                    'success' => true,
                    'message' => 'If an account exists with this email, you will receive a password reset code.',
                ], 200);
            }

            // Generate and send OTP
            $otp = $this->otpService->generateAndSend(
                user: $user,
                purpose: OtpPurposes::RESET_PASSWORD->value,
                channel: OtpChannels::EMAIL->value
            );

            Log::info('Password reset OTP sent', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Password reset code sent to your email.',
                'data' => [
                    'expires_at' => $otp->expires_at->toIso8601String(),
                    'expires_in_minutes' => now()->diffInMinutes($otp->expires_at, false),
                ],
            ], 200);

        } catch (Throwable $e) {
            Log::error('Password reset OTP send failed', [
                'email' => $request->input('email'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send password reset code. Please try again.',
            ], 500);
        }
    }

    /**
     * Verify password reset OTP.
     */
    public function verifyOtp(VerifyPasswordResetOtpRequest $request): JsonResponse
    {
        try {
            $user = User::where('email', $request->input('email'))->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid email or verification code.',
                ], 422);
            }

            $result = $this->otpService->verify(
                user: $user,
                code: $request->input('code'),
                purpose: OtpPurposes::RESET_PASSWORD->value,
                channel: OtpChannels::EMAIL->value
            );

            if ($result['success']) {
                // Generate a temporary token for password reset
                $resetToken = bin2hex(random_bytes(32));
                
                // Store reset token in cache for 15 minutes
                cache()->put(
                    "password_reset:{$resetToken}",
                    ['user_id' => $user->id, 'email' => $user->email],
                    now()->addMinutes(15)
                );

                Log::info('Password reset OTP verified', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Verification code confirmed. You can now reset your password.',
                    'data' => [
                        'reset_token' => $resetToken,
                        'expires_in_minutes' => 15,
                    ],
                ], 200);
            }

            $statusCode = match ($result['error_code'] ?? null) {
                'OTP_BLOCKED' => 429,
                'OTP_EXPIRED' => 410,
                default => 422,
            };

            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'error_code' => $result['error_code'] ?? 'OTP_VERIFICATION_FAILED',
                'remaining_attempts' => $result['remaining_attempts'] ?? null,
                'retry_after' => $result['retry_after'] ?? null,
            ], $statusCode);

        } catch (Throwable $e) {
            Log::error('Password reset OTP verification failed', [
                'email' => $request->input('email'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to verify code. Please try again.',
            ], 500);
        }
    }

    /**
     * Reset password with verified token.
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        try {
            return DB::transaction(function () use ($request) {
                $resetToken = $request->input('reset_token');
                
                // Retrieve reset data from cache
                $resetData = cache()->get("password_reset:{$resetToken}");

                if (!$resetData) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid or expired reset token. Please request a new password reset.',
                    ], 422);
                }

                $user = User::find($resetData['user_id']);

                if (!$user || $user->email !== $resetData['email']) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid reset token.',
                    ], 422);
                }

                // Update password
                $user->update([
                    'password_hash' => Hash::make($request->input('password')),
                ]);

                // Invalidate all existing tokens for security
                $user->tokens()->delete();

                // Remove reset token from cache
                cache()->forget("password_reset:{$resetToken}");

                Log::info('Password reset successful', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Password reset successfully. You can now login with your new password.',
                ], 200);
            });

        } catch (Throwable $e) {
            Log::error('Password reset failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to reset password. Please try again.',
            ], 500);
        }
    }

    /**
     * Resend password reset OTP.
     */
    public function resendOtp(ForgotPasswordRequest $request): JsonResponse
    {
        try {
            $user = User::where('email', $request->input('email'))->first();

            if (!$user) {
                // Don't reveal if user exists for security
                return response()->json([
                    'success' => true,
                    'message' => 'If an account exists with this email, you will receive a password reset code.',
                ], 200);
            }

            $result = $this->otpService->resend(
                user: $user,
                purpose: OtpPurposes::RESET_PASSWORD->value,
                channel: OtpChannels::EMAIL->value
            );

            if ($result['success']) {
                Log::info('Password reset OTP resent', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => $result['message'],
                    'data' => [
                        'expires_at' => $result['expires_at'],
                    ],
                ], 200);
            }

            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'error_code' => $result['error_code'],
                'retry_after' => $result['retry_after'] ?? null,
            ], 429);

        } catch (Throwable $e) {
            Log::error('Password reset OTP resend failed', [
                'email' => $request->input('email'),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to resend code. Please try again.',
            ], 500);
        }
    }
}
