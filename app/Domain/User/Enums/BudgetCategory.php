<?php

namespace App\Domain\User\Enums;

enum BudgetCategory: string
{
    case LOW = 'low';
    case MEDIUM = 'medium';
    case BEST_VALUE = 'best_value';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
