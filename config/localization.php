<?php

return [
    'supported_locales' => [
        'en' => [
            'code' => 'en',
            'name' => 'English',
            'native_name' => 'English',
        ],
        'ar' => [
            'code' => 'ar',
            'name' => 'Arabic',
            'native_name' => 'العربية',
        ],
    ],

    'default_locale' => env('APP_LOCALE', 'en'),
];
