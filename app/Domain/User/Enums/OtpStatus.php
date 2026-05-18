<?php

namespace App\Domain\User\Enums;

enum OtpStatus: string
{
    case PENDING = 'pending';
    case VERIFIED = 'verified';
    case EXPIRED = 'expired';
    case BLOCKED = 'blocked';
}
