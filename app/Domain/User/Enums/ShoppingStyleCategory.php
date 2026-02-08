<?php

namespace App\Domain\User\Enums;

enum ShoppingStyleCategory: string
{
    case ONLINE = 'online';
    case BASED_ON_OFFER = 'based_on_offer';
    case IN_STORE = 'in_store';
    case BEST_DISCOUNT = 'best_discount';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
