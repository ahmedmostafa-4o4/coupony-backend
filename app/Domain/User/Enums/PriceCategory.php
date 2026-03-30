<?php

namespace App\Domain\User\Enums;

enum PriceCategory: string
{
    case BUDGET = 'budget';
    case MID_RANGE = 'mid_range';
    case PREMIUM = 'premium';
    case ALL_LEVELS = 'all_levels';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
