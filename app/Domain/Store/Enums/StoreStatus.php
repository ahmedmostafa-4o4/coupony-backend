<?php

namespace App\Domain\Store\Enums;

enum StoreStatus: string
{
    case PENDING = 'pending';
    case ACTIVE = 'active';
    case REJECTED = 'rejected';
    case SUSPENDED = 'suspended';
    case CLOSED = 'closed';
}

