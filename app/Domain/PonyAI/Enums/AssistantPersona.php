<?php

namespace App\Domain\PonyAI\Enums;

enum AssistantPersona: string
{
    case CUSTOMER = 'customer';
    case SELLER = 'seller';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
