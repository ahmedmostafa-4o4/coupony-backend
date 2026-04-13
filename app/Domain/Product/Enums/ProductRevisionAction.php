<?php

namespace App\Domain\Product\Enums;

enum ProductRevisionAction: string
{
    case CREATE = 'create';
    case UPDATE = 'update';
    case RESUBMIT = 'resubmit';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
