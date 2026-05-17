<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

/**
 * Per-user (or per-IP fallback) throttle for the Pony AI endpoints.
 *
 * Apply as `pony.throttle:text` or `pony.throttle:image` on a route. Limits are
 * pulled from config('pony.rate_limits.<bucket>'), so they can be tuned by env.
 */
class PonyAIRateLimiter
{
    /**
     * @param  \Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, string $bucket = 'text'): Response
    {
        $config = (array) config("pony.rate_limits.{$bucket}", []);

        $maxAttempts = (int) ($config['max_attempts'] ?? 30);
        $decaySeconds = (int) ($config['decay_seconds'] ?? 60);

        if ($maxAttempts <= 0) {
            // A non-positive limit disables throttling for this bucket entirely.
            return $next($request);
        }

        $key = $this->resolveKey($request, $bucket);

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            return response()->json([
                'success' => false,
                'message' => __('api.pony.rate_limited'),
                'retry_after_seconds' => RateLimiter::availableIn($key),
            ], 429)->withHeaders([
                'Retry-After' => (string) RateLimiter::availableIn($key),
                'X-RateLimit-Limit' => (string) $maxAttempts,
                'X-RateLimit-Remaining' => '0',
            ]);
        }

        RateLimiter::hit($key, $decaySeconds);

        /** @var Response $response */
        $response = $next($request);

        return $response->withHeaders([
            'X-RateLimit-Limit' => (string) $maxAttempts,
            'X-RateLimit-Remaining' => (string) max(0, $maxAttempts - RateLimiter::attempts($key)),
        ]);
    }

    private function resolveKey(Request $request, string $bucket): string
    {
        $userId = $request->user()?->getAuthIdentifier();

        $subject = $userId !== null
            ? 'user:'.$userId
            : 'ip:'.($request->ip() ?? 'unknown');

        return "pony:{$bucket}:{$subject}";
    }
}
