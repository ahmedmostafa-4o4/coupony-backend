<?php

namespace App\Domain\Product\Support;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class IdentifierCodeResolver
{
    public function __construct(
        private readonly ArabicSlugTransliterator $transliterator,
    ) {
    }

    public function resolveCategoryCode(array $labels, ?string $fallbackTitle = null): string
    {
        $sources = collect($labels)
            ->filter(fn($value) => is_string($value) && trim($value) !== '')
            ->push($fallbackTitle)
            ->filter(fn($value) => is_string($value) && trim($value) !== '')
            ->map(fn(string $value) => $this->transliterator->transliterate($value, '-'))
            ->values();

        foreach (config('product_identifiers.category_codes', []) as $code => $keywords) {
            foreach ((array) $keywords as $keyword) {
                $keyword = Str::lower((string) $keyword);

                foreach ($sources as $source) {
                    if ($source !== '' && Str::contains($source, $keyword)) {
                        return Str::upper($code);
                    }
                }
            }
        }

        return 'GEN';
    }

    public function resolveNameCode(?string $value, string $fallback = 'PRD'): string
    {
        $token = $this->firstMeaningfulToken($value);

        if ($token === null) {
            return Str::upper($fallback);
        }

        return Str::upper(Str::substr($token, 0, 3));
    }

    public function resolveAttributeCode(?string $value, string $fallback = 'GEN'): string
    {
        if (!is_string($value) || trim($value) === '') {
            return Str::upper($fallback);
        }

        $compact = $this->compactTransliteratedValue($value);
        $mapped = Arr::get(config('product_identifiers.attribute_codes', []), $compact);

        if (is_string($mapped) && $mapped !== '') {
            return Str::upper($mapped);
        }

        $token = $this->firstMeaningfulToken($value);

        if ($token === null) {
            return Str::upper($fallback);
        }

        if (ctype_digit($token)) {
            return $token;
        }

        return Str::upper(Str::substr($token, 0, 3));
    }

    public function canonicalAttributeName(?string $value): ?string
    {
        $compact = $this->compactTransliteratedValue($value);

        if ($compact === '') {
            return null;
        }

        foreach (config('product_identifiers.attribute_name_aliases', []) as $canonical => $aliases) {
            foreach ((array) $aliases as $alias) {
                if ($compact === Str::lower((string) $alias)) {
                    return $canonical;
                }
            }
        }

        return null;
    }

    public function firstMeaningfulToken(?string $value): ?string
    {
        $transliterated = $this->transliterator->transliterate($value, '-');

        if ($transliterated === '') {
            return null;
        }

        $stopWords = collect(config('product_identifiers.stop_words', []))
            ->map(fn($word) => Str::lower((string) $word))
            ->all();

        $token = collect(explode('-', $transliterated))
            ->map(fn(string $item) => Str::lower(trim($item)))
            ->first(fn(string $item) => $item !== '' && !in_array($item, $stopWords, true));

        return $token ?: null;
    }

    public function compactTransliteratedValue(?string $value): string
    {
        $transliterated = $this->transliterator->transliterate($value, '-');

        return Str::lower(str_replace('-', '', $transliterated));
    }
}
