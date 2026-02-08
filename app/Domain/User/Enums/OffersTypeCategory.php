<?php

namespace App\Domain\User\Enums;

enum OffersTypeCategory: string
{
    case PERCENTAGE_OFFER = 'percentage_offer';
    case FIXED_OFFER = 'fixed_offer';
    case BUY_AND_GET = 'buy_and_get';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
