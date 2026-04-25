<?php

namespace App\Domain\Product\Support;

final class ProductDirectUpdateFields
{
    public const TOP_LEVEL = [
        'title',
        'description',
        'is_featured',
    ];

    public const VARIANT = [
        'title',
        'barcode',
        'is_active',
        'is_default',
        'sort_order',
        'inventory_mode',
        'stock_qty',
        'low_stock_threshold',
        'allow_backorder',
    ];

    public const IMAGE = [
        'sort_order',
        'is_primary',
    ];

    public static function isTopLevelDirect(string $field): bool
    {
        return in_array($field, self::TOP_LEVEL, true);
    }

    public static function isVariantFieldDirect(string $field): bool
    {
        return in_array($field, self::VARIANT, true);
    }

    public static function isImageFieldDirect(string $field): bool
    {
        return in_array($field, self::IMAGE, true);
    }
}
