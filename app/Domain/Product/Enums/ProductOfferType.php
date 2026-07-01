<?php

namespace App\Domain\Product\Enums;

enum ProductOfferType: string
{
    case FIXED = 'fixed';
    case PERCENTAGE = 'percentage';
    case BUY_X_GET_Y = 'buy_x_get_y';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return __("analytics.offer_types.{$this->value}");
    }
}
