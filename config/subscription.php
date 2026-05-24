<?php

return [
    'is_review_mode' => env('SUBSCRIPTION_REVIEW_MODE', false),
    'payment_session_ttl_minutes' => env('SUBSCRIPTION_SESSION_TTL', 30),
    'expiring_soon_days' => env('SUBSCRIPTION_EXPIRING_SOON_DAYS', 3),
    'default_grace_period_days' => 7,
    'default_degraded_period_days' => 14,
    'paymob' => [
        'secret_key' => env('PAYMOB_SECRET_KEY'),
        'public_key' => env('PAYMOB_PUBLIC_KEY'),
        'api_key' => env('PAYMOB_API_KEY'),
        'integration_id' => env('PAYMOB_INTEGRATION_ID'),
        'hmac_secret' => env('PAYMOB_HMAC_SECRET'),
        'base_url' => env('PAYMOB_BASE_URL', 'https://accept.paymob.com'),
    ],
    'supported_payment_methods' => ['card', 'wallet'],
];
