<?php

namespace App\Application\Http\Controllers\API\V1;

use App\Application\Http\Controllers\Controller;
use App\Application\Http\Requests\UpdateLanguageRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\App;

class LocaleController extends Controller
{
    public function index(): JsonResponse
    {
        $supportedLocales = config('localization.supported_locales', []);
        $defaultLocale = config('localization.default_locale', config('app.locale'));

        $locales = array_values(array_map(
            fn (array $locale): array => [
                'code' => $locale['code'],
                'name' => $locale['name'],
                'native_name' => $locale['native_name'],
                'is_default' => $locale['code'] === $defaultLocale,
            ],
            $supportedLocales
        ));

        return $this->localizedJson([
            'data' => $locales,
        ]);
    }

    public function update(UpdateLanguageRequest $request): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        $language = $request->validated('language');
        $user = $request->user();

        $user->forceFill([
            'language' => $language,
        ])->save();

        App::setLocale($language);

        return $this->localizedJson([
            'message' => __('api.locale.updated'),
            'data' => [
                'language' => $user->language,
            ],
        ]);
    }
}
