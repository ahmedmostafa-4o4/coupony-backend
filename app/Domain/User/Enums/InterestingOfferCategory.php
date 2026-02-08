<?php

namespace App\Domain\User\Enums;

enum InterestingOfferCategory: string
{
    case RESTAURANTS = 'restaurants';
    case FASHION = 'fashion';
    case SUPERMARKET = 'supermarket';
    case ELECTRONICS = 'electronics';
    case PHARMACY = 'pharmacy';
    case BEAUTY = 'beauty';
    case TRAVEL = 'travel';
    case OTHER = 'other';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
