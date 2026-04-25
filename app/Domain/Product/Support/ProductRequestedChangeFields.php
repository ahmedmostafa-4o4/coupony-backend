<?php

namespace App\Domain\Product\Support;

final class ProductRequestedChangeFields
{
    public const ALLOWED = [
        'short_description' => ['value'],
        'currency' => ['value'],
        'sku' => ['value'],
        'category_ids' => ['value'],

        'images' => [
            'file',
        ],

        'variants' => [
            'option_summary',
            'sku',
            'original_price',
            'currency',
        ],

        'variant_attributes' => [
            'attribute_name',
            'attribute_value',
            'sort_order',
        ],

        'offer' => [
            'type',
            'status',
            'label',
            'starts_at',
            'ends_at',
            'claim_expiration_minutes',
            'fixed_amount',
            'percentage_value',
            'max_discount',
            'buy_qty',
            'get_qty',
            'allow_mix_buy_variants',
            'allow_mix_reward_variants',
            'buy_variant_skus',
            'reward_variant_skus',
        ],
    ];

    public static function sections(): array
    {
        return array_keys(self::ALLOWED);
    }

    public static function allowedFor(string $section): array
    {
        return self::ALLOWED[$section] ?? [];
    }

    public static function isAllowed(string $section, ?string $field): bool
    {
        $field = $field ?: 'value';

        return in_array($field, self::allowedFor($section), true);
    }
}
