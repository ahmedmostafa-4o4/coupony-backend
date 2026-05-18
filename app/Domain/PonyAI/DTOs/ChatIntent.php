<?php

namespace App\Domain\PonyAI\DTOs;

final class ChatIntent
{
    /**
     * @param  array<int, string>  $attributes
     * @param  array<int, string>  $keywords
     */
    public function __construct(
        public readonly string $freeText,
        public readonly ?int $categoryId = null,
        public readonly ?float $priceMin = null,
        public readonly ?float $priceMax = null,
        public readonly array $attributes = [],
        public readonly ?string $semanticQuery = null,
        public readonly ?string $arabicQuery = null,
        public readonly array $keywords = [],
        public readonly bool $isGenericCatalogRequest = false,
    ) {}

    /**
     * Build a ChatIntent from a Gemini JSON payload, ignoring fields that
     * do not match the expected types. The caller's free text is the source
     * of truth and never overwritten by the model.
     *
     * @param  array<string, mixed>  $payload
     */
    public static function fromGeminiPayload(array $payload, string $freeText): self
    {
        $categoryId = is_int($payload['category_id'] ?? null) ? (int) $payload['category_id'] : null;

        $priceMin = self::nonNegativeFloat($payload['price_min'] ?? null);
        $priceMax = self::nonNegativeFloat($payload['price_max'] ?? null);

        if ($priceMin !== null && $priceMax !== null && $priceMax < $priceMin) {
            [$priceMin, $priceMax] = [$priceMax, $priceMin];
        }

        $semanticQuery = self::cleanString($payload['semantic_query'] ?? null);
        $arabicQuery = self::cleanString($payload['arabic_query'] ?? null);

        $attributes = self::cleanLowerStringList($payload['attributes'] ?? null);
        $keywords = self::cleanLowerStringList($payload['keywords'] ?? null);

        $isGeneric = (bool) ($payload['is_generic_catalog_request'] ?? false);

        return new self(
            freeText: $freeText,
            categoryId: $categoryId,
            priceMin: $priceMin,
            priceMax: $priceMax,
            attributes: $attributes,
            semanticQuery: $semanticQuery,
            arabicQuery: $arabicQuery,
            keywords: $keywords,
            isGenericCatalogRequest: $isGeneric,
        );
    }

    /**
     * Return a single multilingual query string suitable for embedding or
     * for soft-ranking against product titles/descriptions. Order matters:
     * the model-emitted semantic / arabic expansions go first, then the
     * user's original text, then any aliases.
     */
    public function combinedQueryText(): string
    {
        $parts = array_filter([
            $this->semanticQuery,
            $this->arabicQuery,
            $this->freeText,
            $this->keywords === [] ? null : implode(' ', $this->keywords),
        ], static fn ($value) => is_string($value) && trim($value) !== '');

        return trim(implode(' ', $parts));
    }

    private static function nonNegativeFloat(mixed $value): ?float
    {
        if (is_int($value) || is_float($value)) {
            return $value >= 0 ? (float) $value : null;
        }

        if (is_string($value) && is_numeric($value)) {
            $float = (float) $value;

            return $float >= 0 ? $float : null;
        }

        return null;
    }

    private static function cleanString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    /**
     * @return array<int, string>
     */
    private static function cleanLowerStringList(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $out = [];

        foreach ($value as $item) {
            if (! is_string($item)) {
                continue;
            }

            $clean = trim(mb_strtolower($item));

            if ($clean !== '') {
                $out[] = $clean;
            }
        }

        return array_values(array_unique($out));
    }
}
