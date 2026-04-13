<?php

namespace App\Domain\Product\Enums;

enum ProductOfferTargetRole: string
{
    case BUY = 'buy';
    case REWARD = 'reward';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
