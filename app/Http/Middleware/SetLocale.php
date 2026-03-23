<?php

namespace App\Http\Middleware;

use App;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {

        $allowed = ['en', 'ar'];

        // Example headers:
        // Accept-Language: en
        // Accept-Language: ar
        // Accept-Language: en-US,en;q=0.9
        $header = $request->header('Accept-Language', config('app.locale'));

        $locale = strtolower(trim(explode(',', $header)[0])); // en-US
        $locale = explode('-', $locale)[0]; // en

        if (!in_array($locale, $allowed, true)) {
            $locale = config('app.fallback_locale', 'en');
        }

        App::setLocale($locale);

        $response = $next($request);
        $response->headers->set('Content-Language', $locale);

        return $response;
    }
}
