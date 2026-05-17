<?php

namespace App\Domain\PonyAI\Services\Pipeline;

use App\Domain\PonyAI\Contracts\GeminiClient;
use App\Domain\PonyAI\DTOs\ChatIntent;
use App\Domain\PonyAI\Exceptions\GeminiException;
use App\Domain\Product\Models\Category;

class IntentExtractor
{
    public function __construct(private readonly GeminiClient $gemini)
    {
    }

    public function extract(string $prompt): ChatIntent
    {
        $trimmed = trim($prompt);

        if ($trimmed === '') {
            return new ChatIntent(freeText: '');
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
            // Intent extraction failures degrade to a free-text-only intent.
            // The downstream retriever still runs and gets useful results.
            return new ChatIntent(freeText: $trimmed);
        }

        return ChatIntent::fromGeminiPayload($result->decodeJson(), $trimmed);
    }

    private function buildGeminiPrompt(string $userPrompt): string
    {
        $categoryHints = $this->buildCategoryHint();

        return <<<PROMPT
You are an intent extraction service for a shopping marketplace search.
Read the user's message and emit a JSON object with these keys (omit any key you cannot fill):

  {
    "category_id":  <integer | null>,     // category from the catalog, see hints below
    "price_min":    <number | null>,      // minimum acceptable price, same currency as the user
    "price_max":    <number | null>,      // maximum acceptable price
    "attributes":   ["string", ...]       // free-form attribute tokens (color, size, material, brand)
  }

Rules:
- Output JSON only.
- Use null for missing values; do not invent numbers.
- "attributes" should be short lowercase tokens, no full sentences.

Categories catalog (id -> name, partial):
{$categoryHints}

User message:
"""
{$userPrompt}
"""
PROMPT;
    }

    private function buildCategoryHint(): string
    {
        $rows = Category::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->limit(40)
            ->get(['id', 'name_en']);

        if ($rows->isEmpty()) {
            return '(no active categories)';
        }

        return $rows
            ->map(fn(Category $category) => sprintf('  %d -> %s', $category->id, (string) ($category->name_en ?? '')))
            ->implode("\n");
    }
}
