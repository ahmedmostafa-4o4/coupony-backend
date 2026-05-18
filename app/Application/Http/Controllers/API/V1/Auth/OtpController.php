<?php

namespace App\Application\Http\Controllers\API\V1\Auth;

use App\Application\Http\Controllers\Controller;
use App\Domain\User\Enums\OtpChannels;
use App\Domain\User\Enums\OtpPurposes;
use App\Domain\User\Models\User;
use App\Domain\User\Services\AuthenticationService;
use App\Domain\User\Services\OtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class OtpController extends Controller
{
    public function __construct(
        private OtpService $otpService,
        private AuthenticationService $authService
    ) {}

    /**
     * Send OTP to user.
     */
    public function send(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required_without:phone_number|email|exists:users,email',
            'phone_number' => 'required_without:email|string|exists:users,phone_number',
            'purpose' => ['required', Rule::in($this->otpPurposeValues())],
            'channel' => ['nullable', Rule::in($this->otpChannelValues())],
        ]);

        // Find user
        $user = $this->findUser($request);

        if (! $user) {
            return response()->json([
                'message' => __('api.otp.user_not_found'),
            ], 404);
        }

        // Determine channel
        $channel = $validated['channel'] ?? $this->determineChannel($validated['purpose']);

        // Generate and send OTP
        try {
            $otp = $this->otpService->generateAndSend(
                user: $user,
                purpose: $validated['purpose'],
                channel: $channel
            );

            return response()->json([
                'message' => __('api.otp.sent_successfully'),
                'data' => [
                    'expires_at' => $otp->expires_at->toIso8601String(),
                    'expires_in_minutes' => now()->diffInMinutes($otp->expires_at, false),
                    'channel' => $channel,
                    'masked_recipient' => $this->otpService->maskRecipient($otp->phone_or_email, $channel),
                ],
            ], 200);

        } catch (\Exception $e) {
            Log::error('OTP Send Error', [
                'user_id' => $user->id,
                'purpose' => $validated['purpose'],
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => __('api.otp.send_failed'),
            ], 500);
        }
    }

    /**
     * Verify OTP code.
     */
    public function verify(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required_without:phone_number|email',
            'phone_number' => 'required_without:email|string',
            'code' => 'required|string|digits:6',
            'purpose' => ['required', Rule::in($this->otpPurposeValues())],
            'channel' => ['nullable', Rule::in($this->otpChannelValues())],
        ]);

        $user = $this->findUser($request);

        if (! $user) {
            return response()->json([
                'message' => __('api.otp.user_not_found'),
            ], 404);
        }

        $channel = $validated['channel'] ?? $this->determineChannel($validated['purpose']);
        $wasEmailVerified = $user->getIsVerifiedAttribute();

        $result = $this->otpService->verify(
            user: $user,
            code: $validated['code'],
            purpose: $validated['purpose'],
            channel: $channel
        );

        if ($result['success']) {
            $responseData = [
                'verified' => true,
                'purpose' => $validated['purpose'],
            ];

            if (
                $validated['purpose'] === OtpPurposes::VERIFY_EMAIL->value &&
                ! $wasEmailVerified
            ) {
                $context = [
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'device_name' => $request->input('device_name') ?? null,
                ];

                try {
                    $tokens = $this->authService->issueTokensForUser($user, $context);
                    $responseData = array_merge($responseData, [
                        'user' => $tokens['user'],
                        'session' => $tokens['session'],
                        'access_token' => $tokens['access_token'],
                        'refresh_token' => $tokens['refresh_token'],
                        'token_type' => $tokens['token_type'],
                        'expires_in' => $tokens['expires_in'],
                    ]);
                } catch (\Throwable $e) {
                    Log::error('Failed to issue tokens after OTP verify', [
                        'user_id' => $user->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            return response()->json([
                'message' => $result['message'],
                'data' => $responseData,
            ], 200);
        }

        $statusCode = match ($result['error_code'] ?? null) {
            'OTP_BLOCKED' => 429,
            'OTP_EXPIRED' => 410,
            default => 422,
        };

        return response()->json([
            'message' => $result['message'],
            'error_code' => $result['error_code'] ?? 'OTP_VERIFICATION_FAILED',
            'remaining_attempts' => $result['remaining_attempts'] ?? null,
            'retry_after' => $result['retry_after'] ?? null,
        ], $statusCode);
    }

    /**
     * Resend OTP.
     */
    public function resend(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required_without:phone_number|email',
            'phone_number' => 'required_without:email|string',
            'purpose' => ['required', Rule::in($this->otpPurposeValues())],
            'channel' => ['nullable', Rule::in($this->otpChannelValues())],
        ]);

        $user = $this->findUser($request);

        if (! $user) {
            return response()->json([
                'message' => __('api.otp.user_not_found'),
            ], 404);
        }

        $channel = $validated['channel'] ?? $this->determineChannel($validated['purpose']);

        $result = $this->otpService->resend(
            user: $user,
            purpose: $validated['purpose'],
            channel: $channel
        );

        if ($result['success']) {
            return response()->json([
                'message' => $result['message'],
                'data' => [
                    'expires_at' => $result['expires_at'],
                ],
            ], 200);
        }

        return response()->json([
            'message' => $result['message'],
            'error_code' => $result['error_code'],
            'retry_after' => $result['retry_after'] ?? null,
        ], 429);
    }

    /**
     * Find user by email or phone.
     */
    private function findUser(Request $request): ?User
    {
        if ($request->filled('email')) {
            return User::where('email', $request->input('email'))->first();
        }

        if ($request->filled('phone_number')) {
            return User::where('phone_number', $request->input('phone_number'))->first();
        }

        return null;
    }

    /**
     * Determine channel based on purpose.
     */
    private function determineChannel(string $purpose): string
    {
        return match ($purpose) {
            OtpPurposes::VERIFY_EMAIL->value, OtpPurposes::RESET_PASSWORD->value => OtpChannels::EMAIL->value,
            OtpPurposes::VERIFY_PHONE->value, OtpPurposes::LOGIN->value => OtpChannels::SMS->value,
            default => OtpChannels::EMAIL->value,
        };
    }

    /**
     * @return list<string>
     */
    private function otpPurposeValues(): array
    {
        return array_map(
            static fn (OtpPurposes $purpose): string => $purpose->value,
            OtpPurposes::cases()
        );
    }

    /**
     * @return list<string>
     */
    private function otpChannelValues(): array
    {
        return array_map(
            static fn (OtpChannels $channel): string => $channel->value,
            OtpChannels::cases()
        );
    }
}
