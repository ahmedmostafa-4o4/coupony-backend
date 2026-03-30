<?php

namespace App\Domain\User\Enums;

enum CustomerReachMethodEnum: string
{
    case PHYSICAL_STORE = 'physical_store';
    case ONLINE_ONLY = 'online_only';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
