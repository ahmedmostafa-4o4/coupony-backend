<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
    ],

    'recommendation' => [
        'base_url' => env('RECOMMENDATION_SERVICE_BASE_URL', 'https://ahmedmostafa56-ml-recommendation-service.hf.space'),
        'timeout' => (int) env('RECOMMENDATION_SERVICE_TIMEOUT', 10),
        'enabled' => filter_var(env('RECOMMENDATION_SERVICE_ENABLED', true), FILTER_VALIDATE_BOOL),
        'seed_limit' => (int) env('RECOMMENDATION_SERVICE_SEED_LIMIT', 20),
    ],

    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        'base_url' => env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'),
        'text_model' => env('GEMINI_TEXT_MODEL', 'gemini-2.5-flash'),
        'vision_model' => env('GEMINI_VISION_MODEL', 'gemini-2.5-flash'),
        'embed_model' => env('GEMINI_EMBED_MODEL', 'text-embedding-004'),
        'timeout' => (int) env('GEMINI_TIMEOUT', 20),
        'retries' => (int) env('GEMINI_RETRIES', 1),
        'fake' => filter_var(env('GEMINI_FAKE', false), FILTER_VALIDATE_BOOL),
    ],

];
