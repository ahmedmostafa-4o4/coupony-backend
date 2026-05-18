<?php

namespace App\Domain\PonyAI\DTOs;

final class GeminiResult
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public readonly string $text,
        public readonly ?string $model = null,
        public readonly ?int $promptTokens = null,
        public readonly ?int $completionTokens = null,
        public readonly array $raw = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function decodeJson(): array
    {
        $trimmed = trim($this->text);

        if ($trimmed === '') {
            return [];
        }

        $stripped = preg_replace('/^```(?:json)?\s*|\s*```$/im', '', $trimmed) ?? $trimmed;
        $decoded = json_decode($stripped, true);

        return is_array($decoded) ? $decoded : [];
    }
}
