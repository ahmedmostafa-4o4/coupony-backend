<?php

namespace App\Application\Http\Controllers\API\V1\Auth;

use App\Application\Http\Controllers\Controller;
use App\Application\Http\Requests\GoogleLoginRequest;
use App\Application\Http\Resources\UserResource;
use App\Domain\User\Actions\RegisterUser;
use App\Domain\User\DTOs\UserData;
use App\Domain\User\Models\User;
use App\Domain\User\Services\AuthenticationService;
use App\Domain\User\Services\GoogleTokenVerifier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class GoogleLoginController extends Controller
{
    public function __construct(
        private AuthenticationService $authService,
        private GoogleTokenVerifier $googleTokenVerifier,
        private RegisterUser $registerUser,
    ) {
    }

    public function __invoke(GoogleLoginRequest $request)
    {
        $context = [
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'device_name' => $request->input('device_name'),
            'role' => $request->input('role'),
        ];

        try {
            $googleUser = $this->googleTokenVerifier->verifyIdToken($request->input('id_token'));

            $user = $this->resolveUserFromGooglePayload($googleUser, $request, $context);

            if ($user->status !== 'active') {
                throw ValidationException::withMessages([
                    'email' => [__('api.auth.account_suspended')],
                ]);
            }

            $requestedRole = $request->input('role');

            $user->loadMissing(['profile', 'roles', 'stores']);

            $result = $this->authService->issueTokensForUser($user, $context);

            $responseUser = $user->fresh()->loadMissing(['profile', 'roles', 'stores']);
            $isOnboardingCompleted = $this->isOnboardingCompleted($responseUser->id, $requestedRole);

            if ($requestedRole === 'seller') {
                return $this->localizedJson([
                    'message' => __('api.auth.login_successful'),
                    'data' => [
                        'user' => new UserResource($responseUser),
                        'session' => $result['session'],
                        'role' => $requestedRole,
                        'is_onboarding_completed' => $isOnboardingCompleted,
                        'is_store_owner' => $responseUser->stores()->exists(),
                        'access_token' => $result['access_token'],
                        'refresh_token' => $result['refresh_token'],
                        'token_type' => $result['token_type'],
                        'expires_in' => $result['expires_in'],
                    ],
                ], 200);
            }

            return $this->localizedJson([
                'message' => __('api.auth.login_successful'),
                'data' => [
                    'user' => new UserResource($responseUser),
                    'session' => $result['session'],
                    'role' => $requestedRole,
                    'is_onboarding_completed' => $isOnboardingCompleted,
                    'access_token' => $result['access_token'],
                    'refresh_token' => $result['refresh_token'],
                    'token_type' => $result['token_type'],
                    'expires_in' => $result['expires_in'],
                ],
            ], 200);
        } catch (ValidationException $e) {
            return $this->localizedJson([
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 401);
        } catch (\Throwable $e) {
            Log::error('Google login failed', [
                'error' => $e->getMessage(),
                'email' => $request->input('email'),
            ]);

            return $this->localizedJson([
                'message' => __('api.auth.login_failed'),
            ], 500);
        }
    }

    private function resolveUserFromGooglePayload(array $googleUser, GoogleLoginRequest $request, array $context): User
    {
        $providerId = (string) $googleUser['sub'];
        $email = Str::lower((string) $googleUser['email']);

        return DB::transaction(function () use ($providerId, $email, $googleUser, $request, $context) {
            $user = User::where('provider', 'google')
                ->where('provider_id', $providerId)
                ->first();

            if (!$user) {
                $user = User::where('email', $email)->first();
            }

            if (!$user) {
                $names = $this->extractNames($googleUser);

                $user = $this->registerUser->execute(
                    new UserData(
                        firstName: $names['first_name'],
                        lastName: $names['last_name'],
                        email: $email,
                        phone_number: null,
                        password: Str::password(32),
                        role: $request->input('role'),
                        provider: 'google',
                        providerId: $providerId,
                        language: $request->input('language', app()->getLocale()),
                    ),
                    $context
                );

                $user->markEmailAsVerified();

                return $user->fresh();
            }

            if ($user->provider !== null && $user->provider !== 'google') {
                throw ValidationException::withMessages([
                    'id_token' => [__('api.auth.invalid_credentials')],
                ]);
            }

            if ($user->provider === 'google' && $user->provider_id !== null && $user->provider_id !== $providerId) {
                throw ValidationException::withMessages([
                    'id_token' => [__('api.auth.invalid_credentials')],
                ]);
            }

            $user->forceFill([
                'email' => $email,
                'provider' => 'google',
                'provider_id' => $providerId,
                'language' => $request->input('language', $user->language ?? app()->getLocale()),
            ])->save();

            $user->markEmailAsVerified();

            if (!$user->profile && ($googleUser['given_name'] ?? null || $googleUser['family_name'] ?? null || $googleUser['name'] ?? null)) {
                $names = $this->extractNames($googleUser);

                $user->profile()->create([
                    'first_name' => $names['first_name'],
                    'last_name' => $names['last_name'],
                ]);
            }

            return $user->fresh();
        });
    }

    private function extractNames(array $googleUser): array
    {
        $firstName = trim((string) ($googleUser['given_name'] ?? ''));
        $lastName = trim((string) ($googleUser['family_name'] ?? ''));

        if ($firstName !== '' || $lastName !== '') {
            return [
                'first_name' => $firstName,
                'last_name' => $lastName,
            ];
        }

        $fullName = trim((string) ($googleUser['name'] ?? ''));

        if ($fullName === '') {
            return [
                'first_name' => '',
                'last_name' => '',
            ];
        }

        $parts = preg_split('/\s+/', $fullName) ?: [];
        $firstName = array_shift($parts) ?? '';

        return [
            'first_name' => $firstName,
            'last_name' => implode(' ', $parts),
        ];
    }

    private function isOnboardingCompleted(string $userId, ?string $role): bool
    {
        return match ($role) {
            'customer' => DB::table('interests')->where('user_id', $userId)->exists(),
            'seller' => DB::table('shop_interests')->where('user_id', $userId)->exists(),
            default => false,
        };
    }
}
