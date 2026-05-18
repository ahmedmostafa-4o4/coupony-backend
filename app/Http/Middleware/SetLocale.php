<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Laravel\Sanctum\PersonalAccessToken;
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
        $allowed = array_keys(config('localization.supported_locales', []));
        $header = $request->header('Accept-Language');
        $locale = $this->parseHeaderLocale($header, $allowed);

        if (! $locale) {
            $locale = $this->resolveAuthenticatedLocale($request);
        }

        if (! in_array($locale, $allowed, true)) {
            $locale = config('app.fallback_locale', config('localization.default_locale', 'en'));
        }

        App::setLocale($locale);
        $request->attributes->set('resolved_locale', $locale);

        $response = $next($request);
        $response->headers->set(
            'Content-Language',
            $request->attributes->get('resolved_locale', App::currentLocale())
        );

        return $response;
    }

    private function parseHeaderLocale(?string $header, array $allowed): ?string
    {
        if (! $header) {
            return null;
        }

        $primaryLocale = strtolower(trim(explode(',', $header)[0]));
        $locale = explode('-', $primaryLocale)[0];

        return in_array($locale, $allowed, true) ? $locale : null;
    }

    private function resolveAuthenticatedLocale(Request $request): ?string
    {
        $token = $request->bearerToken();

        if (! $token) {
            $authorizationHeader = $request->header('Authorization');
            if (is_string($authorizationHeader) && preg_match('/Bearer\s+(.+)/i', $authorizationHeader, $matches)) {
                $token = trim($matches[1]);
            }
        }

        if (! $token) {
            $authorizationHeader = $request->server('HTTP_AUTHORIZATION');
            if (is_string($authorizationHeader) && preg_match('/Bearer\s+(.+)/i', $authorizationHeader, $matches)) {
                $token = trim($matches[1]);
            }
        }

        if (! $token) {
            return null;
        }

        $accessToken = PersonalAccessToken::findToken($token);

        return $accessToken?->tokenable?->language;
    }
}
