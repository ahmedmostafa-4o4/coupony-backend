<?php

namespace App\Domain\Product\Enums;

enum InventoryMode: string
{
    case TRACKED = 'tracked';
    case UNLIMITED = 'unlimited';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
