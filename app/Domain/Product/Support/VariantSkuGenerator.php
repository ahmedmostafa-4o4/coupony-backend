<?php

namespace App\Domain\Product\Support;

use Illuminate\Support\Str;

class VariantSkuGenerator
{
    public function __construct(
        private readonly IdentifierCodeResolver $codes,
    ) {
    }

    public function generateMany(array $variants, ?string $productTitle, array $categoryLabels = []): array
    {
        $used = [];

        foreach ($variants as $variant) {
            $sku = $this->normalizedSkuKey($variant['sku'] ?? null);

            if ($sku !== null) {
                $used[$sku] = true;
            }
        }

        $generated = [];

        foreach ($variants as $index => $variant) {
            $skuKey = $this->normalizedSkuKey($variant['sku'] ?? null);

            if ($skuKey !== null) {
                $generated[$index] = $variant;

                continue;
            }

            $baseSku = $this->baseSku($variant, $productTitle, $categoryLabels);
            $candidate = $this->ensureUnique($baseSku, $used);
            $used[$this->normalizedSkuKey($candidate)] = true;

            $generated[$index] = [
                ...$variant,
                'sku' => $candidate,
            ];
        }

        ksort($generated);

        return array_values($generated);
    }

    private function baseSku(array $variant, ?string $productTitle, array $categoryLabels): string
    {
        $parts = [
            'VAR',
            $this->codes->resolveCategoryCode($categoryLabels, $productTitle),
            $this->codes->resolveNameCode($productTitle, 'PRD'),
        ];

        foreach ($this->attributeCodes($variant['attributes'] ?? []) as $code) {
            $parts[] = $code;
        }

        return Str::upper(implode('-', $parts));
    }

    private function attributeCodes(array $attributes): array
    {
        $collection = collect($attributes)
            ->filter(fn($attribute) => is_array($attribute))
            ->map(function (array $attribute) {
                return [
                    'name' => Str::lower(trim((string) ($attribute['attribute_name'] ?? ''))),
                    'value' => (string) ($attribute['attribute_value'] ?? ''),
                ];
            })
            ->filter(fn(array $attribute) => $attribute['value'] !== '');

        $prioritized = collect(['color', 'colour', 'size'])
            ->flatMap(fn(string $name) => $collection->filter(fn(array $attribute) => $attribute['name'] === $name)->take(1))
            ->values();

        $fallback = $collection
            ->reject(function (array $attribute) use ($prioritized) {
                return $prioritized->contains(fn(array $selected) => $selected['name'] === $attribute['name'] && $selected['value'] === $attribute['value']);
            })
            ->take(2 - $prioritized->count())
            ->values();

        return $prioritized
            ->concat($fallback)
            ->take(2)
            ->map(fn(array $attribute) => $this->codes->resolveAttributeCode($attribute['value']))
            ->values()
            ->all();
    }

    private function ensureUnique(string $baseSku, array $used): string
    {
        $candidate = $this->trimToMaxLength($baseSku);
        $suffix = 2;

        while (array_key_exists(Str::lower($candidate), $used)) {
            $candidate = $this->trimToMaxLength("{$baseSku}-{$suffix}");
            $suffix++;
        }

        return $candidate;
    }

    private function normalizedSkuKey(mixed $sku): ?string
    {
        if (!is_string($sku) || trim($sku) === '') {
            return null;
        }

        return Str::lower(trim($sku));
    }

    private function trimToMaxLength(string $value): string
    {
        return Str::substr($value, 0, 100);
    }
}
