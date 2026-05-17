<?php

namespace App\Domain\PonyAI\Services\Pipeline;

use App\Domain\PonyAI\Contracts\GeminiClient;
use App\Domain\PonyAI\DTOs\ImageQueryUnderstanding;
use App\Domain\PonyAI\Exceptions\GeminiException;

class ImageQueryUnderstander
{
    public function __construct(private readonly GeminiClient $gemini)
    {
    }

    public function understand(string $imageBytes, string $mimeType): ImageQueryUnderstanding
    {
        try {
            $result = $this->gemini->describeImage(
                $imageBytes,
                $mimeType,
                $this->instruction(),
            );
        } catch (GeminiException) {
            return new ImageQueryUnderstanding(caption: '');
        }

        $payload = $result->decodeJson();

        return ImageQueryUnderstanding::fromGeminiPayload($payload, fallbackCaption: $result->text);
    }

    private function instruction(): string
    {
        return <<<'PROMPT'
You are looking at a product image for a shopping search. Reply with JSON only,
matching exactly this schema:

  {
    "caption":        "<short factual description, under 30 words>",
    "category_guess": "<single product category, e.g. 'sneakers', 'wallet', 'headphones'>",
    "color":          "<dominant color of the product, single word>",
    "attributes":     ["short", "lowercase", "tokens"]   // brand, material, style, etc.
  }

Rules:
- Output JSON only. No surrounding prose, no markdown fences.
- Use null for any field you cannot fill confidently.
- "attributes" is at most 6 short lowercase tokens.
PROMPT;
    }
}
