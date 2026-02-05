<?php

namespace App\Application\Http\Controllers\API\V1\Auth;
use App\Application\Http\Controllers\Controller;
use App\Application\Http\Requests\loginUserRequest;
use App\Application\Http\Resources\UserResource;
use App\Domain\User\Services\AuthenticationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
            // 'currency' => $request->header('X-Currency', 'USD'),
            'device_name' => $request->input('device_name'),
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
                return response()->json([
                    'message' => 'Login successful',
                    'data' => [
                        'user' => new UserResource($result['user']->load('stores')),
                        'role' => 'seller',
                        'is_store_owner' => $is_store_owner,
                        'next' => $is_store_owner ? null : [
                            'url' => route('store.create'),   // API endpoint to call
                            'method' => 'POST',               // HTTP method
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




            return response()->json([
                'message' => 'Login successful',
                'data' => [
                    'user' => new UserResource($result['user']),
                    'role' => 'customer',
                    'access_token' => $result['access_token'],
                    'refresh_token' => $result['refresh_token'],
                    'token_type' => $result['token_type'],
                    'expires_in' => $result['expires_in'],
                ],
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Invalid credentials',
                'errors' => $e->errors(),
            ], 401);
        }


    }

    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();
        $currentToken = $request->bearerToken();

        $this->authService->logout($user, $currentToken);

        return response()->json([
            'message' => 'Logged out successfully',
        ], 200);
    }

    public function me(Request $request): JsonResponse
    {
        // $user = $request->user()->load(['profile', 'preferences', 'points']);
        $user = $request->user();

        return response()->json([
            'data' => new UserResource($user),
        ], 200);
    }
}
