<?php

namespace App\Domain\User\Services;

use App\Domain\User\Enums\OtpChannels;
use App\Domain\User\Enums\OtpPurposes;
use App\Domain\User\Events\UserLoggedIn;
use App\Domain\User\Events\UserLoggedOut;
use App\Domain\User\Models\Session;
use App\Domain\User\Models\User;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\NewAccessToken;
use Spatie\Permission\Models\Role;

class AuthenticationService
{
    private const REFRESH_TOKEN_TTL_DAYS = 30;

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
        $requestedRole = $context['role'] ?? null;
        $user = User::where('email', $email)->first();

        if (! $user || ! $this->hasher->check($password, $user->password_hash)) {
            Log::warning('Login failed: invalid credentials', [
                'email' => $email,
                'user_id' => $user?->id,
            ]);

            throw ValidationException::withMessages([
                'email' => [__('api.auth.invalid_credentials')],
            ]);
        }

        if ($user->status !== 'active') {
            throw ValidationException::withMessages([
                'email' => [__('api.auth.account_suspended')],
            ]);
        }

        if (! $user->getIsVerifiedAttribute()) {
            $otp = $this->otpService->generateAndSend(
                user: $user,
                purpose: OtpPurposes::VERIFY_EMAIL->value,
                channel: OtpChannels::EMAIL->value,
            );

            throw ValidationException::withMessages([
                'otp' => [__('api.auth.verification_code_sent')],
            ]);
        }

        $this->assertUserCanLoginWithRole($user, $requestedRole);

        // Generate tokens
        $accessToken = $this->generateAccessToken($user, $context);
        $refreshToken = $this->generateRefreshToken($user);

        $user->update([
            'last_login_at' => now(),
            'login_count' => $user->login_count + 1,
            'last_ip' => $context['ip_address'] ?? null,
        ]);

        $session = $user->sessions()->create([
            'token' => $accessToken->accessToken->token,
            'refresh_token' => hash('sha256', $refreshToken),
            'ip_address' => $context['ip_address'] ?? null,
            'user_agent' => $context['user_agent'] ?? null,
            'device_type' => $this->detectDeviceType($context['user_agent'] ?? ''),
            'expires_at' => $this->refreshSessionExpiresAt(),
            'last_activity' => now()->timestamp,
        ]);

        if ($requestedRole === 'customer' && ! $user->hasRole($requestedRole)) {
            $user->assignRole($requestedRole);
            $user->userRoles()->updateOrCreate([
                'role_id' => Role::where('name', $requestedRole)->first()->id,
                'user_id' => $user->id,
                'store_id' => null,
            ], [
                'granted_at' => now(),
            ]);
        }

        $responseUser = $user->fresh()->loadMissing(['profile', 'roles', 'stores']);

        event(new UserLoggedIn($responseUser, $context));

        return [
            'user' => $responseUser,
            'session' => $session,
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
        $requestedRole = $context['role'] ?? null;

        if ($requestedRole === 'customer' && ! $user->hasRole($requestedRole)) {
            $user->assignRole($requestedRole);
            $user->userRoles()->updateOrCreate([
                'role_id' => Role::where('name', $requestedRole)->first()->id,
                'user_id' => $user->id,
                'store_id' => null,
            ], [
                'granted_at' => now(),
            ]);
        }
        $this->assertUserCanLoginWithRole($user, $context['role'] ?? null);

        // Generate tokens
        $accessToken = $this->generateAccessToken($user, $context);
        $refreshToken = $this->generateRefreshToken($user);

        $user->update([
            'last_login_at' => now(),
            'login_count' => $user->login_count + 1,
            'last_ip' => $context['ip_address'] ?? null,
        ]);

        $session = $user->sessions()->create([
            'token' => $accessToken->accessToken->token,
            'refresh_token' => hash('sha256', $refreshToken),
            'ip_address' => $context['ip_address'] ?? null,
            'user_agent' => $context['user_agent'] ?? null,
            'device_type' => $this->detectDeviceType($context['user_agent'] ?? ''),
            'expires_at' => $this->refreshSessionExpiresAt(),
            'last_activity' => now()->timestamp,
        ]);

        $responseUser = $user->fresh()->loadMissing(['profile', 'roles', 'stores']);

        event(new UserLoggedIn($responseUser, $context));

        return [
            'user' => $responseUser,
            'session' => $session,
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
        return bin2hex(random_bytes(32));
    }

    public function refreshToken(string $refreshToken, array $context = []): array
    {
        $hashedToken = hash('sha256', $refreshToken);
        $session = Session::query()
            ->where('refresh_token', $hashedToken)
            ->first();

        if (! $session || ! $session->user) {
            throw (new ModelNotFoundException)->setModel(Session::class);
        }

        if ($session->isExpired()) {
            $session->user->tokens()->where('token', $session->token)->delete();
            $session->delete();

            throw (new ModelNotFoundException)->setModel(Session::class);
        }

        $user = $session->user;

        if ($user->status !== 'active') {
            throw ValidationException::withMessages([
                'email' => [__('api.auth.account_suspended')],
            ]);
        }

        $user->tokens()->where('token', $session->token)->delete();
        $session->delete();

        // Generate new tokens
        $accessToken = $this->generateAccessToken($user, $context);
        $newRefreshToken = $this->generateRefreshToken($user);

        // Create new session
        $user->sessions()->create([
            'token' => $accessToken->accessToken->token,
            'refresh_token' => hash('sha256', $newRefreshToken),
            'ip_address' => $context['ip_address'] ?? null,
            'user_agent' => $context['user_agent'] ?? null,
            'device_type' => $this->detectDeviceType($context['user_agent'] ?? ''),
            'expires_at' => $this->refreshSessionExpiresAt(),
            'last_activity' => now()->timestamp,
        ]);

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

        return [];
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

        return DB::table('personal_access_tokens')
            ->where('token', $hashedToken)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->exists();
    }

    public function logout(User $user, ?string $currentToken = null): void
    {
        // Revoke specific token or all tokens
        if ($currentToken) {
            $hashedToken = $this->hashPlainTextAccessToken($currentToken);

            $user->tokens()
                ->where('token', $hashedToken)
                ->delete();
            $user->sessions()
                ->where('token', $hashedToken)
                ->delete();
        } else {
            $this->logoutAll($user);

            return;
        }

        event(new UserLoggedOut($user));
    }

    public function logoutAll(User $user): void
    {
        $user->tokens()->delete();
        $user->sessions()->delete();
    }

    private function refreshSessionExpiresAt()
    {
        return now()->addDays(self::REFRESH_TOKEN_TTL_DAYS);
    }

    private function hashPlainTextAccessToken(string $token): string
    {
        return hash('sha256', explode('|', $token)[1] ?? '');
    }

    private function assertUserCanLoginWithRole(User $user, ?string $requestedRole): void
    {
        if ($requestedRole === 'admin' && ! $user->hasRole('admin')) {
            throw ValidationException::withMessages([
                'role' => [__('api.common.unauthorized')],
            ]);
        }

        if ($requestedRole === 'seller' && ! $user->hasAnyRole(['seller', 'seller_pending'])) {
            throw ValidationException::withMessages([
                'role' => [__('api.common.unauthorized')],
            ]);
        }
    }
}
