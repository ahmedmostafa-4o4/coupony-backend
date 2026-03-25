<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Log;
use Symfony\Component\HttpFoundation\Response;

class ContactUsThrottle
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $routeName = $request->route()?->getName();

        match ($routeName) {
            'contactUs.seller' => $key = 'contact-us-seller:' . $request->ip(),
            'contactUs.customer' => $key = 'contact-us-customer:' . $request->ip(),
            'notifyMe.submit' => $key = 'notify-me:' . $request->ip(),
            default => abort(400, __('api.contact.invalid_route')),
        };

        if (RateLimiter::tooManyAttempts($key, 3)) {
            return response()->json([
                'message' => __('api.contact.rate_limited'),
                'attempts' => RateLimiter::attempts($key),
                'retry_after_seconds' => RateLimiter::availableIn($key),
            ], 429);
        }

        RateLimiter::hit($key, 60);

        return $next($request);
    }
}
