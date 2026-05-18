<?php

return [

    /*
    |--------------------------------------------------------------------------
    | OTP Expiry Time
    |--------------------------------------------------------------------------
    |
    | The number of minutes an OTP code remains valid.
    |
    */
    'expiry_minutes' => env('OTP_EXPIRY_MINUTES', 10),

    /*
    |--------------------------------------------------------------------------
    | Maximum Attempts
    |--------------------------------------------------------------------------
    |
    | The maximum number of failed verification attempts before blocking.
    |
    */
    'max_attempts' => env('OTP_MAX_ATTEMPTS', 3),

    /*
    |--------------------------------------------------------------------------
    | Test OTP Code
    |--------------------------------------------------------------------------
    |
    | OTP code to use in local/testing environments for easy testing.
    |
    */
    'test_code' => env('OTP_TEST_CODE', '123456'),

    /*
    |--------------------------------------------------------------------------
    | Code Length
    |--------------------------------------------------------------------------
    |
    | Length of the OTP code (typically 4 or 6 digits).
    |
    */
    'code_length' => env('OTP_CODE_LENGTH', 6),

];
