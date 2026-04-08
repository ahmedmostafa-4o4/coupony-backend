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
        private readonly bool $hasCategoryIds,
        private readonly bool $hasImages,
        private readonly bool $hasVariants,
    ) {
    }

    public static function fromRequest(FormRequest $request): self
    {
        $attributeKeys = [
            'title',
            'slug',
            'short_description',
            'description',
            'product_type',
            'base_price',
            'compare_at_price',
            'currency',
            'sku',
            'status',
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

        return new self(
            attributes: $attributes,
            categoryIds: array_values($request->input('category_ids', []) ?? []),
            images: $images,
            variants: $variants,
            hasCategoryIds: $request->exists('category_ids'),
            hasImages: $request->exists('images'),
            hasVariants: $request->exists('variants'),
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
}
