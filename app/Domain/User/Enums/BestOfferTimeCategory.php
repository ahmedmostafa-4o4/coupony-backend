<?php

namespace App\Domain\User\Enums;

enum BestOfferTimeCategory: string
{
    case ALL_WEEK = 'all_week';
    case WEEKENDS_OCCASIONS = 'weekends_occasions';
    case OFF_PEAK = 'off_peak';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
