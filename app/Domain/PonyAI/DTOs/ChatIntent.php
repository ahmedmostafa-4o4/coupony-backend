<?php

namespace App\Domain\PonyAI\DTOs;

final class ChatIntent
{
    /**
     * @param  array<int, string>  $attributes
     */
    public function __construct(
        public readonly string $freeText,
        public readonly ?int $categoryId = null,
        public readonly ?float $priceMin = null,
        public readonly ?float $priceMax = null,
        public readonly array $attributes = [],
    ) {
    }

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

        $attributes = [];
        if (is_array($payload['attributes'] ?? null)) {
            foreach ($payload['attributes'] as $attribute) {
                if (is_string($attribute) && trim($attribute) !== '') {
                    $attributes[] = trim(mb_strtolower($attribute));
                }
            }
            $attributes = array_values(array_unique($attributes));
        }

        return new self(
            freeText: $freeText,
            categoryId: $categoryId,
            priceMin: $priceMin,
            priceMax: $priceMax,
            attributes: $attributes,
        );
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
}
