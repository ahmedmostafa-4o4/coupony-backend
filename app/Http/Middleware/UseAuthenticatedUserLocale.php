<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class UseAuthenticatedUserLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $allowed = array_keys(config('localization.supported_locales', []));
        $header = $request->header('Accept-Language');

        if (!$this->hasSupportedHeaderLocale($header, $allowed)) {
            $userLocale = $this->resolveUserLocale($request);

            if (in_array($userLocale, $allowed, true)) {
                App::setLocale($userLocale);
            }
        }

        return $next($request);
    }

    private function hasSupportedHeaderLocale(?string $header, array $allowed): bool
    {
        if (!$header) {
            return false;
        }

        $primaryLocale = strtolower(trim(explode(',', $header)[0]));
        $locale = explode('-', $primaryLocale)[0];

        return in_array($locale, $allowed, true);
    }

    private function resolveUserLocale(Request $request): ?string
    {
        $userLocale = $request->user()?->language;

        if ($userLocale) {
            return $userLocale;
        }

        $token = $request->bearerToken();

        if (!$token) {
            $authorizationHeader = $request->header('Authorization');
            if (is_string($authorizationHeader) && preg_match('/Bearer\s+(.+)/i', $authorizationHeader, $matches)) {
                $token = trim($matches[1]);
            }
        }

        if (!$token) {
            $authorizationHeader = $request->server('HTTP_AUTHORIZATION');
            if (is_string($authorizationHeader) && preg_match('/Bearer\s+(.+)/i', $authorizationHeader, $matches)) {
                $token = trim($matches[1]);
            }
        }

        if (!$token) {
            return null;
        }

        return PersonalAccessToken::findToken($token)?->tokenable?->language;
    }
}
