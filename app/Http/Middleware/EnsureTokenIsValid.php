<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTokenIsValid
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => __('api.common.unauthenticated'),
            ], 401);
        }

        // Check if token is expired
        $token = $user->currentAccessToken();

        if ($token && $token->expires_at && $token->expires_at->isPast()) {
            return response()->json([
                'message' => __('api.middleware.token_expired'),
                'error_code' => 'TOKEN_EXPIRED',
            ], 401);
        }

        return $next($request);
    }
}
