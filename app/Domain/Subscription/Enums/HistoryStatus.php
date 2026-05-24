<?php

namespace App\Domain\Subscription\Enums;

enum HistoryStatus: string
{
    case ACTIVE = 'active';
    case EXPIRED = 'expired';
    case REFUNDED = 'refunded';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';
}
