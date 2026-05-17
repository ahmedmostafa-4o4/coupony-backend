<?php

namespace App\Domain\PonyAI\Services\Pipeline;

use App\Domain\PonyAI\Contracts\GeminiClient;
use App\Domain\PonyAI\DTOs\SellerIntent;
use App\Domain\PonyAI\Enums\SellerTopic;
use App\Domain\PonyAI\Exceptions\GeminiException;

class SellerIntentExtractor
{
    public function __construct(private readonly GeminiClient $gemini)
    {
    }

    public function extract(string $prompt): SellerIntent
    {
        $trimmed = trim($prompt);

        if ($trimmed === '') {
            return new SellerIntent(freeText: '');
        }

        try {
            $result = $this->gemini->generateJson(
                $this->buildGeminiPrompt($trimmed),
                [
                    'temperature' => 0.1,
                    'max_output_tokens' => 256,
                ],
            );
        } catch (GeminiException) {
            return new SellerIntent(freeText: $trimmed);
        }

        return SellerIntent::fromGeminiPayload($result->decodeJson(), $trimmed);
    }

    private function buildGeminiPrompt(string $userPrompt): string
    {
        $allowedTopics = implode("'|'", SellerTopic::values());

        return <<<PROMPT
You are an intent classifier for a seller-facing analytics assistant.
Read the seller's message and emit a JSON object with these keys:

  {
    "topic":   "<'{$allowedTopics}'>",
    "filters": { ... }    // optional structured filters (currency, days, min_views, etc.)
  }

Rules:
- Output JSON only.
- "topic" must be exactly one of the allowed values.
- Use "free_form" when nothing fits.

Seller message:
"""
{$userPrompt}
"""
PROMPT;
    }
}
