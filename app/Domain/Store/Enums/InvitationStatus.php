<?php

namespace App\Domain\Store\Enums;

enum InvitationStatus: string
{
    case PENDING = 'pending';
    case ACCEPTED = 'accepted';
    case DECLINED = 'declined';
    case CANCELLED = 'cancelled';
    case EXPIRED = 'expired';
}
