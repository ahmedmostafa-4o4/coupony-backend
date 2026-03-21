<?php

namespace App\Domain\Store\Enums;

enum VerificationStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
}
