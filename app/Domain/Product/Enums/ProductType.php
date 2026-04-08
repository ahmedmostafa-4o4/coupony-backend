<?php

namespace App\Domain\Product\Enums;

enum ProductType: string
{
    case STANDARD = 'standard';
    case SERVICE = 'service';
    case COUPONABLE_ITEM = 'couponable_item';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
