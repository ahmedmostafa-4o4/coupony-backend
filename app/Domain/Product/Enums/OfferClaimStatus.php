<?php

namespace App\Domain\Product\Enums;

enum OfferClaimStatus: string
{
    case ACTIVE = 'active';
    case REDEEMED = 'redeemed';
    case EXPIRED = 'expired';
    case CANCELLED = 'cancelled';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
