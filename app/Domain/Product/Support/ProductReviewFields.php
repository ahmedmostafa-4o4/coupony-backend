<?php

namespace App\Domain\Product\Support;

final class ProductReviewFields
{
    public const SHORT_DESCRIPTION = 'short_description';

    public const CURRENCY = 'currency';

    public const SKU = 'sku';

    public const CATEGORY_IDS = 'category_ids';

    public const IMAGES = 'images';

    public const VARIANTS = 'variants';

    public const OFFER = 'offer';

    public const REVIEWABLE = [
        self::SHORT_DESCRIPTION,
        self::CURRENCY,
        self::SKU,
        self::CATEGORY_IDS,
        self::IMAGES,
        self::VARIANTS,
        self::OFFER,
    ];

    public const DIRECT = [
        'is_featured',
    ];

    public static function requiresReview(string $field): bool
    {
        return in_array($field, self::REVIEWABLE, true);
    }

    public static function isKnown(string $field): bool
    {
        return in_array($field, [...self::REVIEWABLE, ...self::DIRECT], true);
    }

    public static function reviewable(): array
    {
        return self::REVIEWABLE;
    }

    public static function direct(): array
    {
        return self::DIRECT;
    }
}
