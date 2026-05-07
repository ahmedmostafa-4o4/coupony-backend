<?php

namespace App\Application\Http\Controllers\API\V1\Auth;

use App\Application\Http\Controllers\Controller;
use App\Domain\User\Services\AuthenticationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RefreshTokenController extends Controller
{
    public function __construct(
        private AuthenticationService $authService
    ) {
    }

    /**
     * Refresh access token
     */
    public function __invoke(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'refresh_token' => 'required|string',
            ]);

            $context = [
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ];

            $result = $this->authService->refreshToken(
                $request->input('refresh_token'),
                $context
            );

            return response()->json([
                'message' => __('api.auth.token_refreshed'),
                'data' => $result,
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => __('api.common.validation_failed'),
                'errors' => $e->errors(),
            ], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => __('api.auth.invalid_refresh_token'),
            ], 401);
        } catch (\Exception $e) {
            Log::error('Token refresh failed', ['error' => $e->getMessage()]);
            
            return response()->json([
                'message' => __('api.auth.refresh_failed'),
            ], 401);
        }
    }
}
