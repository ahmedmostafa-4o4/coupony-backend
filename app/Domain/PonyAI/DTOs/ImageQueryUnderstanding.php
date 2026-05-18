<?php

namespace App\Domain\PonyAI\DTOs;

final class ImageQueryUnderstanding
{
    /**
     * @param  array<int, string>  $attributes
     */
    public function __construct(
        public readonly string $caption,
        public readonly ?string $categoryGuess = null,
        public readonly ?string $color = null,
        public readonly array $attributes = [],
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromGeminiPayload(array $payload, string $fallbackCaption = ''): self
    {
        $caption = is_string($payload['caption'] ?? null) ? trim($payload['caption']) : '';

        if ($caption === '') {
            $caption = trim($fallbackCaption);
        }

        $category = is_string($payload['category_guess'] ?? null)
            ? trim($payload['category_guess'])
            : null;
        $color = is_string($payload['color'] ?? null)
            ? trim($payload['color'])
            : null;

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
            caption: $caption,
            categoryGuess: ($category === '' ? null : $category),
            color: ($color === '' ? null : $color),
            attributes: $attributes,
        );
    }

    public function toIntent(string $extraUserMessage = ''): ChatIntent
    {
        $tokens = [];

        if ($this->caption !== '') {
            $tokens[] = $this->caption;
        }

        if ($extraUserMessage !== '') {
            $tokens[] = $extraUserMessage;
        }

        $attributes = $this->attributes;
        if ($this->color !== null && $this->color !== '') {
            $attributes[] = mb_strtolower($this->color);
        }
        if ($this->categoryGuess !== null && $this->categoryGuess !== '') {
            $attributes[] = mb_strtolower($this->categoryGuess);
        }

        return new ChatIntent(
            freeText: trim(implode(' ', $tokens)),
            attributes: array_values(array_unique($attributes)),
        );
    }
}
