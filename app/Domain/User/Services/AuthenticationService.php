<?php

namespace App\Domain\User\Services;

use App\Application\Http\Resources\UserResource;
use App\Domain\User\Enums\OtpChannels;
use App\Domain\User\Enums\OtpPurposes;
use App\Domain\User\Events\UserLoggedIn;
use App\Domain\User\Events\UserLoggedOut;
use App\Domain\User\Models\User;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\NewAccessToken;

class AuthenticationService
{
    /**
     * Create a new class instance.
     */
    public function __construct(
        private Hasher $hasher,
        private OtpService $otpService
    ) {
        //
    }

    public function login(string $email, string $password, array $context = [])
    {
        $user = User::where('email', $email)->first();

        if (!$user || !$this->hasher->check($password, $user->password_hash)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if ($user->status !== 'active') {
            throw ValidationException::withMessages([
                'email' => ['Your account has been suspended. Please contact support.'],
            ]);
        }

        if (!$user->getIsVerifiedAttribute()) {
            $otp = $this->otpService->generateAndSend(
                user: $user,
                purpose: OtpPurposes::VERIFY_EMAIL->value,
                channel: OtpChannels::EMAIL->value,
            );

            throw ValidationException::withMessages([
                'otp' => ['A verification code has been sent to your registered email. Please verify to proceed.'],
            ]);
        }

        // Generate tokens
        $accessToken = $this->generateAccessToken($user, $context);
        $refreshToken = $this->generateRefreshToken($user);

        $user->update([
            'last_login_at' => now(),
            'login_count' => $user->login_count + 1,
            'last_ip' => $context['ip_address'] ?? null,
        ]);

        $user->sessions()->create([
            'token' => hash('sha256', $accessToken->plainTextToken),
            'ip_address' => $context['ip_address'] ?? null,
            'user_agent' => $context['user_agent'] ?? null,
            'device_type' => $this->detectDeviceType($context['user_agent'] ?? ''),
            'expires_at' => now()->addMinutes(config('sanctum.expiration', 60)),
            'last_activity' => now()->timestamp,
        ]);



        event(new UserLoggedIn($user, $context));

        return [
            // 'user' => new UserResource($user->load('profile', 'preferences')),
            'user' => new UserResource($user),
            'access_token' => $accessToken->plainTextToken,
            'refresh_token' => $refreshToken,
            'token_type' => 'Bearer',
            'expires_in' => config('sanctum.expiration', 60) * 60, // seconds
        ];
    }

    /**
     * Issue access & refresh tokens for a user without password verification.
     */
    public function issueTokensForUser(User $user, array $context = []): array
    {
        // Generate tokens
        $accessToken = $this->generateAccessToken($user, $context);
        $refreshToken = $this->generateRefreshToken($user);

        $user->update([
            'last_login_at' => now(),
            'login_count' => $user->login_count + 1,
            'last_ip' => $context['ip_address'] ?? null,
        ]);

        $user->sessions()->create([
            'token' => hash('sha256', $accessToken->plainTextToken),
            'ip_address' => $context['ip_address'] ?? null,
            'user_agent' => $context['user_agent'] ?? null,
            'device_type' => $this->detectDeviceType($context['user_agent'] ?? ''),
            'expires_at' => now()->addMinutes(config('sanctum.expiration', 60)),
            'last_activity' => now()->timestamp,
        ]);

        event(new UserLoggedIn($user, $context));

        return [
            'user' => new UserResource($user->load('profile')),
            'access_token' => $accessToken->plainTextToken,
            'refresh_token' => $refreshToken,
            'token_type' => 'Bearer',
            'expires_in' => config('sanctum.expiration', 60) * 60,
        ];
    }

    private function generateAccessToken(User $user, array $context = []): NewAccessToken
    {
        $abilities = $this->getUserAbilities($user);

        $tokenName = $context['device_name'] ??
            $context['user_agent'] ??
            'web-token';

        return $user->createToken(
            name: $tokenName,
            abilities: $abilities,
            expiresAt: now()->addMinutes(config('sanctum.expiration', 60))
        );
    }

    private function generateRefreshToken(User $user): string
    {
        $refreshToken = bin2hex(random_bytes(32));

        $user->update([
            'remember_token' => hash('sha256', $refreshToken),
        ]);

        return $refreshToken;
    }

    public function refreshToken(string $refreshToken): array
    {
        $hashedToken = hash('sha256', $refreshToken);

        $user = User::where('remember_token', $hashedToken)
            ->where('status', 'active')
            ->firstOrFail();

        // Revoke old access tokens
        $user->tokens()->delete();

        // Generate new tokens
        $accessToken = $this->generateAccessToken($user);
        $newRefreshToken = $this->generateRefreshToken($user);

        return [
            'access_token' => $accessToken->plainTextToken,
            'refresh_token' => $newRefreshToken,
            'token_type' => 'Bearer',
            'expires_in' => config('sanctum.expiration', 60) * 60,
        ];
    }

    /**
     * Get user abilities based on roles
     */
    private function getUserAbilities(User $user): array
    {
        $abilities = ['*']; // Default: all abilities

        // If using spatie/laravel-permission
        if ($user->hasRole('admin')) {
            return ['*'];
        }

        if ($user->hasRole('seller')) {
            return [
                'product:create',
                'product:read',
                'product:update',
                'product:delete',
                'order:read',
                'order:update',
                'store:manage',
            ];
        }

        if ($user->hasRole('customer')) {
            return [
                'product:read',
                'order:create',
                'order:read',
                'cart:manage',
                'profile:update',
            ];
        }

        return $abilities;
    }

    /**
     * Detect device type from user agent
     */
    private function detectDeviceType(string $userAgent): string
    {
        if (preg_match('/mobile|android|iphone|ipad/i', $userAgent)) {
            return 'mobile';
        }
        if (preg_match('/tablet|ipad/i', $userAgent)) {
            return 'tablet';
        }
        return 'desktop';
    }

    /**
     * Verify token is valid
     */
    public function verifyToken(string $token): bool
    {
        $hashedToken = hash('sha256', explode('|', $token)[1] ?? '');

        return \DB::table('personal_access_tokens')
            ->where('token', $hashedToken)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->exists();
    }

    public function logout(User $user, string $currentToken = null): void
    {
        // Revoke specific token or all tokens
        if ($currentToken) {
            $user->tokens()
                ->where('token', hash('sha256', explode('|', $currentToken)[1] ?? ''))
                ->delete();
        } else {
            $user->tokens()->delete();
        }

        // Clear remember token
        $user->update(['remember_token' => null]);

        // Mark sessions as expired
        $user->sessions()
            ->where('expires_at', '>', now())
            ->update(['expires_at' => now()]);

        event(new UserLoggedOut($user));
    }

    public function logoutAll(User $user): void
    {
        $user->tokens()->delete();
        $user->sessions()->delete();
    }
}
