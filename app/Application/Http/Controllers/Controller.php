<?php

namespace App\Application\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Laravel\Sanctum\PersonalAccessToken;

abstract class Controller
{
    protected function applyAuthenticatedLocale(Request $request): void
    {
        $allowed = array_keys(config('localization.supported_locales', []));
        $header = $request->header('Accept-Language');

        if ($this->hasSupportedHeaderLocale($header, $allowed)) {
            return;
        }

        $userLocale = $request->user()?->language;

        if (!$userLocale) {
            $token = $request->bearerToken();

            if (!$token) {
                $authorizationHeader = $request->header('Authorization', $request->server('HTTP_AUTHORIZATION'));
                if (is_string($authorizationHeader) && preg_match('/Bearer\s+(.+)/i', $authorizationHeader, $matches)) {
                    $token = trim($matches[1]);
                }
            }

            if ($token) {
                $userLocale = PersonalAccessToken::findToken($token)?->tokenable?->language;
            }
        }

        if (in_array($userLocale, $allowed, true)) {
            App::setLocale($userLocale);
            $request->attributes->set('resolved_locale', $userLocale);
        }
    }

    protected function localizedJson(array $payload, int $status = 200): JsonResponse
    {
        return response()
            ->json($payload, $status)
            ->header('Content-Language', App::currentLocale());
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
}
