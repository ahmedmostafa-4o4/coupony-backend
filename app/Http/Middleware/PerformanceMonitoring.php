<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Log;
use Symfony\Component\HttpFoundation\Response;

class PerformanceMonitoring
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $start = microtime(true);

        $response = $next($request);

        $duration = microtime(true) - $start;

        if ($duration > 1.0) {  // Log slow requests
            Log::warning('Slow request', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'duration' => $duration,
                'memory' => memory_get_peak_usage(true) / 1024 / 1024 .'MB',
            ]);
        }

        return $response;
    }
}
