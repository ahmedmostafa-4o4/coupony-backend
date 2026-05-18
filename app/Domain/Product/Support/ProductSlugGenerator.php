<?php

namespace App\Domain\Product\Support;

use App\Domain\Product\Models\Product;

class ProductSlugGenerator
{
    public function __construct(
        private readonly ArabicSlugTransliterator $transliterator,
    ) {}

    public function generate(string $storeId, ?string $title, ?string $ignoreProductId = null): string
    {
        $baseSlug = $this->transliterator->transliterate($title);
        $baseSlug = $baseSlug !== '' ? $baseSlug : 'product';

        return $this->makeUnique($storeId, $baseSlug, $ignoreProductId);
    }

    private function makeUnique(string $storeId, string $baseSlug, ?string $ignoreProductId = null): string
    {
        $candidate = $baseSlug;
        $suffix = 2;

        while ($this->slugExists($storeId, $candidate, $ignoreProductId)) {
            $candidate = "{$baseSlug}-{$suffix}";
            $suffix++;
        }

        return $candidate;
    }

    private function slugExists(string $storeId, string $slug, ?string $ignoreProductId = null): bool
    {
        return Product::query()
            ->withTrashed()
            ->where('store_id', $storeId)
            ->where('slug', $slug)
            ->when($ignoreProductId, fn ($query) => $query->whereKeyNot($ignoreProductId))
            ->exists();
    }
}
