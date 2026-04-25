<?php

namespace App\Domain\Product\Support;

use App\Domain\Product\DTOs\ProductData;
use App\Domain\Product\Models\Category;
use App\Domain\Product\Models\Product;
use App\Domain\Store\Models\Store;

class PrepareProductIdentifiers
{
    public function __construct(
        private readonly ProductSlugGenerator $slugs,
        private readonly ProductSkuGenerator $productSkus,
        private readonly VariantSkuGenerator $variantSkus,
    ) {
    }

    public function forCreate(Store $store, ProductData $data): ProductData
    {
        return $this->prepare($store, $data);
    }

    public function forUpdate(Product $product, ProductData $data): ProductData
    {
        $product->loadMissing('categories:id,name,slug');

        return $this->prepare($product->store, $data, $product);
    }

    private function prepare(Store $store, ProductData $data, ?Product $product = null): ProductData
    {
        $attributes = $data->attributes();
        $title = $this->resolvedTitle($attributes, $product);
        $categoryLabels = $this->categoryLabels($data, $product);

        $attributes = $this->prepareSlug($store->id, $attributes, $title, $product);
        $attributes = $this->prepareProductSku($store->id, $attributes, $title, $categoryLabels, $product);

        $variants = $data->hasVariants()
            ? $this->variantSkus->generateMany($data->variants(), $title, $categoryLabels)
            : $data->variants();

        return $data
            ->withAttributes($attributes)
            ->withVariants($variants, $data->hasVariants());
    }

    private function prepareSlug(string $storeId, array $attributes, ?string $title, ?Product $product): array
    {
        $slugExistsInPayload = array_key_exists('slug', $attributes);
        $shouldGenerate = false;

        if ($product === null) {
            $shouldGenerate = !$slugExistsInPayload || blank($attributes['slug']);
        } elseif ($slugExistsInPayload) {
            $shouldGenerate = blank($attributes['slug']);
        } elseif (blank($product->slug) && array_key_exists('title', $attributes) && filled($title)) {
            $shouldGenerate = true;
        }

        if ($shouldGenerate) {
            $attributes['slug'] = $this->slugs->generate($storeId, $title, $product?->id);
        }

        return $attributes;
    }

    private function prepareProductSku(
        string $storeId,
        array $attributes,
        ?string $title,
        array $categoryLabels,
        ?Product $product,
    ): array {
        $skuExistsInPayload = array_key_exists('sku', $attributes);
        $shouldGenerate = false;

        if ($product === null) {
            $shouldGenerate = !$skuExistsInPayload || blank($attributes['sku']);
        } elseif ($skuExistsInPayload) {
            $shouldGenerate = blank($attributes['sku']);
        } elseif (blank($product->sku) && array_key_exists('title', $attributes) && filled($title)) {
            $shouldGenerate = true;
        }

        if ($shouldGenerate) {
            $attributes['sku'] = $this->productSkus->generate($storeId, $title, $categoryLabels, $product?->id);
        }

        return $attributes;
    }

    private function resolvedTitle(array $attributes, ?Product $product): ?string
    {
        if (array_key_exists('title', $attributes) && filled($attributes['title'])) {
            return (string) $attributes['title'];
        }

        return $product?->title;
    }

    private function categoryLabels(ProductData $data, ?Product $product): array
    {
        $categories = $data->hasCategoryIds()
            ? Category::query()
                ->whereIn('id', $data->categoryIds())
                ->get(['name', 'slug'])
            : ($product?->categories ?? collect());

        return $categories
            ->flatMap(fn($category) => [$category->name, $category->slug])
            ->filter(fn($value) => is_string($value) && trim($value) !== '')
            ->values()
            ->all();
    }
}
