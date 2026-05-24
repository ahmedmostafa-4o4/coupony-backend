<?php

namespace App\Domain\Subscription\Enums;

enum PaymentSessionStatus: string
{
    case PENDING = 'pending';
    case PAID = 'paid';
    case FAILED = 'failed';
    case EXPIRED = 'expired';
}
