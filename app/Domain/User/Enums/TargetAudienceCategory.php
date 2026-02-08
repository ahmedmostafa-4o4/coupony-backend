<?php

namespace App\Domain\User\Enums;

enum TargetAudienceCategory: string
{
    case ALL_CUSTOMERS = 'all_customers';
    case NEW_CUSTOMERS = 'new_customers';
    case SPECIFIC_CATEGORY = 'specific_category';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
