<?php

namespace App\Domain\Product\Support;

use App\Domain\Product\Models\Product;
use Illuminate\Support\Str;

class ProductSkuGenerator
{
    public function __construct(
        private readonly IdentifierCodeResolver $codes,
    ) {
    }

    public function generate(
        string $storeId,
        ?string $title,
        array $categoryLabels = [],
        ?string $ignoreProductId = null,
    ): string {
        $categoryCode = $this->codes->resolveCategoryCode($categoryLabels, $title);
        $nameCode = $this->codes->resolveNameCode($title, 'PRD');
        $baseSku = Str::upper("PRD-{$categoryCode}-{$nameCode}");

        return $this->makeUnique($storeId, $baseSku, $ignoreProductId);
    }

    private function makeUnique(string $storeId, string $baseSku, ?string $ignoreProductId = null): string
    {
        $candidate = $baseSku;
        $suffix = 2;

        while ($this->skuExists($storeId, $candidate, $ignoreProductId)) {
            $candidate = "{$baseSku}-{$suffix}";
            $suffix++;
        }

        return $candidate;
    }

    private function skuExists(string $storeId, string $sku, ?string $ignoreProductId = null): bool
    {
        return Product::query()
            ->withTrashed()
            ->where('store_id', $storeId)
            ->where('sku', $sku)
            ->when($ignoreProductId, fn($query) => $query->whereKeyNot($ignoreProductId))
            ->exists();
    }
}
