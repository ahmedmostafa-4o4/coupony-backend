<?php

namespace App\Application\Http\Controllers\API\V1\Auth;
use App\Application\Http\Controllers\Controller;
use App\Application\Http\Requests\loginUserRequest;
use App\Application\Http\Resources\UserResource;
use App\Domain\User\Services\AuthenticationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LoginController extends Controller
{
    public function __construct(
        private AuthenticationService $authService
    ) {
        //
    }
    public function login(loginUserRequest $request)
    {
        $context = [
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'device_name' => $request->input('device_name'),
            'role' => $request->input('role'),
        ];

        try {
            $result = $this->authService->login(
                email: $request->input('email'),
                password: $request->input('password'),
                context: $context
            );

            $role = $request->input('role');

            if ($role === 'seller') {
                $is_store_owner = $result['user']->stores()->exists();
                return $this->localizedJson([
                    'message' => __('api.auth.login_successful'),
                    'data' => [
                        'user' => new UserResource($result['user']->load('stores')),
                        'session' => $result['session'],
                        'role' => $role,
                        'is_store_owner' => $is_store_owner,
                        'next' => $is_store_owner ? null : [
                            'url' => route('store.create'),
                            'method' => 'POST',
                            'fields' => [
                                'name' => 'string',
                                'description' => 'string',
                                'subscription_tier' => 'free|basic|premium|enterprise',
                                'phone' => 'string',
                                'tax_id' => 'string',
                                'logo' => 'file',
                                'banner' => 'file',
                                'verification_docs' => [
                                    'commercial_register' => 'file',
                                    'tax_card' => 'file',
                                    'id_card' => 'file',
                                ]
                            ]
                        ],
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
                    'user' => new UserResource($result['user']),
                    'session' => $result['session'],
                    'role' => $role,
                    'access_token' => $result['access_token'],
                    'refresh_token' => $result['refresh_token'],
                    'token_type' => $result['token_type'],
                    'expires_in' => $result['expires_in'],
                ],
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->localizedJson([
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 401);
        } catch (\Exception $e) {
            Log::error('Login failed', [
                'email' => $request->input('email'),
                'error' => $e->getMessage(),
            ]);

            return $this->localizedJson([
                'message' => __('api.auth.login_failed'),
            ], 500);
        }
    }

    public function logout(Request $request): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        $user = $request->user();
        $currentToken = $request->bearerToken();

        $this->authService->logout($user, $currentToken);

        return $this->localizedJson([
            'message' => __('api.auth.logout_successful'),
        ], 200);
    }

    public function me(Request $request): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        $user = $request->user();

        return $this->localizedJson([
            'data' => new UserResource($user),
        ], 200);
    }
}
