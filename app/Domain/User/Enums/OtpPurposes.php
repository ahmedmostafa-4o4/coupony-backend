<?php

namespace App\Domain\User\Enums;

enum OtpPurposes: string
{
    case LOGIN = 'login';
    case RESET_PASSWORD = 'reset_password';
    case VERIFY_EMAIL = 'verify_email';
    case VERIFY_PHONE = 'verify_phone';
}
