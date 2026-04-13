<?php

namespace App\Domain\Product\DTOs;

use Illuminate\Foundation\Http\FormRequest;

class ProductData
{
    /**
     * Create a new data transfer object instance.
     */
    public function __construct(
        private readonly array $attributes,
        private readonly array $categoryIds,
        private readonly array $images,
        private readonly array $variants,
        private readonly array $offer,
        private readonly bool $hasCategoryIds,
        private readonly bool $hasImages,
        private readonly bool $hasVariants,
        private readonly bool $hasOffer,
    ) {
    }

    public static function fromRequest(FormRequest $request): self
    {
        $attributeKeys = [
            'title',
            'slug',
            'short_description',
            'description',
            'base_price',
            'compare_at_price',
            'currency',
            'sku',
            'is_featured',
        ];

        $attributes = [];

        foreach ($attributeKeys as $key) {
            if ($request->exists($key)) {
                $attributes[$key] = $request->input($key);
            }
        }

        $imageFiles = $request->allFiles()['images'] ?? [];
        $images = collect($request->input('images', []) ?? [])
            ->values()
            ->map(function (array $image, int $index) use ($imageFiles) {
                return [
                    'file' => data_get($imageFiles, "{$index}.file"),
                    'sort_order' => (int) ($image['sort_order'] ?? 0),
                    'is_primary' => (bool) ($image['is_primary'] ?? false),
                ];
            })
            ->all();

        $variants = collect($request->input('variants', []) ?? [])
            ->values()
            ->map(function (array $variant) {
                return [
                    'title' => $variant['title'],
                    'option_summary' => $variant['option_summary'] ?? null,
                    'sku' => $variant['sku'] ?? null,
                    'barcode' => $variant['barcode'] ?? null,
                    'price' => $variant['price'] ?? null,
                    'compare_at_price' => $variant['compare_at_price'] ?? null,
                    'currency' => $variant['currency'] ?? null,
                    'sort_order' => (int) ($variant['sort_order'] ?? 0),
                    'is_default' => (bool) ($variant['is_default'] ?? false),
                    'is_active' => (bool) ($variant['is_active'] ?? true),
                    'inventory_mode' => $variant['inventory_mode'] ?? null,
                    'stock_qty' => array_key_exists('stock_qty', $variant) && $variant['stock_qty'] !== null
                        ? (int) $variant['stock_qty']
                        : null,
                    'low_stock_threshold' => array_key_exists('low_stock_threshold', $variant) && $variant['low_stock_threshold'] !== null
                        ? (int) $variant['low_stock_threshold']
                        : null,
                    'allow_backorder' => (bool) ($variant['allow_backorder'] ?? false),
                    'attributes' => collect($variant['attributes'] ?? [])
                        ->values()
                        ->map(fn(array $attribute) => [
                            'attribute_name' => $attribute['attribute_name'],
                            'attribute_value' => $attribute['attribute_value'],
                            'sort_order' => (int) ($attribute['sort_order'] ?? 0),
                        ])
                        ->all(),
                ];
            })
            ->all();

        $offerInput = $request->input('offer');
        $offer = is_array($offerInput) ? [
            'type' => $offerInput['type'] ?? null,
            'status' => $offerInput['status'] ?? null,
            'label' => $offerInput['label'] ?? null,
            'starts_at' => $offerInput['starts_at'] ?? null,
            'ends_at' => $offerInput['ends_at'] ?? null,
            'claim_expiration_minutes' => array_key_exists('claim_expiration_minutes', $offerInput) && $offerInput['claim_expiration_minutes'] !== null
                ? (int) $offerInput['claim_expiration_minutes']
                : null,
            'fixed_amount' => $offerInput['fixed_amount'] ?? null,
            'percentage_value' => $offerInput['percentage_value'] ?? null,
            'max_discount' => $offerInput['max_discount'] ?? null,
            'buy_qty' => array_key_exists('buy_qty', $offerInput) && $offerInput['buy_qty'] !== null
                ? (int) $offerInput['buy_qty']
                : null,
            'get_qty' => array_key_exists('get_qty', $offerInput) && $offerInput['get_qty'] !== null
                ? (int) $offerInput['get_qty']
                : null,
            'allow_mix_buy_variants' => (bool) ($offerInput['allow_mix_buy_variants'] ?? false),
            'allow_mix_reward_variants' => (bool) ($offerInput['allow_mix_reward_variants'] ?? false),
            'buy_variant_skus' => array_values($offerInput['buy_variant_skus'] ?? []),
            'reward_variant_skus' => array_values($offerInput['reward_variant_skus'] ?? []),
        ] : [];

        return new self(
            attributes: $attributes,
            categoryIds: array_values($request->input('category_ids', []) ?? []),
            images: $images,
            variants: $variants,
            offer: $offer,
            hasCategoryIds: $request->exists('category_ids'),
            hasImages: $request->exists('images'),
            hasVariants: $request->exists('variants'),
            hasOffer: $request->exists('offer'),
        );
    }

    public function attributes(): array
    {
        return $this->attributes;
    }

    public function categoryIds(): array
    {
        return $this->categoryIds;
    }

    public function images(): array
    {
        return $this->images;
    }

    public function variants(): array
    {
        return $this->variants;
    }

    public function offer(): array
    {
        return $this->offer;
    }

    public function hasCategoryIds(): bool
    {
        return $this->hasCategoryIds;
    }

    public function hasImages(): bool
    {
        return $this->hasImages;
    }

    public function hasVariants(): bool
    {
        return $this->hasVariants;
    }

    public function hasOffer(): bool
    {
        return $this->hasOffer;
    }
}
