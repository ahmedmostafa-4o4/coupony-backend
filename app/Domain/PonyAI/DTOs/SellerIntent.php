<?php

namespace App\Domain\PonyAI\DTOs;

use App\Domain\PonyAI\Enums\SellerTopic;

final class SellerIntent
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function __construct(
        public readonly string $freeText,
        public readonly SellerTopic $topic = SellerTopic::FREE_FORM,
        public readonly array $filters = [],
    ) {
    }

    /**
     * Build from a Gemini JSON payload. Anything we don't recognise is dropped.
     *
     * @param  array<string, mixed>  $payload
     */
    public static function fromGeminiPayload(array $payload, string $freeText): self
    {
        $topic = is_string($payload['topic'] ?? null)
            ? SellerTopic::fromString($payload['topic'])
            : SellerTopic::FREE_FORM;

        $filters = is_array($payload['filters'] ?? null) ? $payload['filters'] : [];

        return new self(
            freeText: $freeText,
            topic: $topic,
            filters: $filters,
        );
    }
}
