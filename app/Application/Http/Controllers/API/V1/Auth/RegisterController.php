<?php

namespace App\Application\Http\Controllers\API\V1\Auth;

use App\Application\Http\Controllers\Controller;
use App\Application\Http\Requests\registerUserRequest;
use App\Domain\User\Actions\RegisterUser;
use App\Domain\User\DTOs\UserData;
use App\Domain\User\Enums\OtpChannels;
use App\Domain\User\Enums\OtpPurposes;
use App\Domain\User\Services\AuthenticationService;
use App\Domain\User\Services\OtpService;
use Illuminate\Container\Attributes\DB;
use Illuminate\Support\Facades\DB as FacadesDB;
use Illuminate\Support\Facades\Log;

class RegisterController extends Controller
{
    public function __construct(
        private RegisterUser $registerUser,
        private OtpService $otpService
    ) {
    }

    public function __invoke(registerUserRequest $request)
    {
        return FacadesDB::transaction(function () use ($request) {
                    try {
            $context = [
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'currency' => $request->header('X-Currency', 'USD'),
                'device_name' => $request->input('device_name'),
            ];
            
            $user = $this->registerUser->execute(
                UserData::fromRequest($request),
                context: $context
            );

            // Generate and send OTP
            try {
                $otp = $this->otpService->generateAndSend(
                    user: $user,
                    purpose: OtpPurposes::VERIFY_EMAIL->value,
                    channel: OtpChannels::EMAIL->value
                );

                return response()->json([
                    'message' => 'Registration successful. Please check your email for verification code.',
                    'data' => [
                        'expires_at' => $otp->expires_at->toIso8601String(),
                        'expires_in_minutes' => now()->diffInMinutes($otp->expires_at, false),
                        'channel' => OtpChannels::EMAIL->value,
                        'masked_recipient' => $this->otpService->maskRecipient($otp->phone_or_email, OtpChannels::EMAIL->value),
                    ],
                ], 201);

            } catch (\Exception $e) {
                Log::error('OTP Send Error', [
                    'user_id' => $user->id,
                    'purpose' => OtpPurposes::VERIFY_EMAIL->value,
                    'error' => $e->getMessage(),
                ]);

                return response()->json([
                    'message' => 'Registration successful but failed to send verification code. Please request a new code.',
                ], 201);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Registration failed', [
                'email' => $request->input('email'),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Registration failed. Please try again later.',
            ], 500);
        }
        });
    }
}
