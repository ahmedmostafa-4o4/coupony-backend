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
                    'max_output_tokens' => 384,
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
You are an intent extraction service for a multilingual shopping marketplace search.
The user may write in Egyptian Arabic, Modern Standard Arabic, English, or a mix.

Read the user message and emit a single JSON object. Use null when unsure - never
invent values. The schema is:

{
  "category_id":                 <integer | null>,
  "price_min":                   <number  | null>,
  "price_max":                   <number  | null>,
  "attributes":                  ["short lowercase tokens", ...],
  "semantic_query":              "<English expansion / paraphrase of what the user wants, or null>",
  "arabic_query":                "<Arabic expansion / paraphrase, or null>",
  "keywords":                    ["Arabic AND English aliases", ...],
  "is_generic_catalog_request":  <true | false>
}

Rules:
- Understand Egyptian Arabic colloquialisms. Examples:
    "كوتشي"  ≈ "sneakers"          ≈ "حذاء رياضي" ≈ "shoes"
    "موبايل" ≈ "phone"             ≈ "هاتف"
    "تلاجة"  ≈ "fridge"            ≈ "ثلاجة"
    "ساعة"   ≈ "watch"
- When the user types one Arabic word, include both the Arabic and the English
  equivalents in "keywords" so downstream search matches either language.
- Do NOT invent category_id. Return null unless the user clearly named a
  category that maps to the catalog below.
- For vague / catalog-browsing prompts like "هل يوجد منتجات", "اعرض المنتجات",
  "ايه عندك", "show me what you have", "any products?", set
  is_generic_catalog_request=true and leave keywords minimal or empty.
- "attributes" should be short lowercase tokens (color, material, brand, etc.).
- Output JSON only - no surrounding prose, no markdown code fences.

Catalog (id -> English name, partial):
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
            ->get(['id', 'name_en', 'name_ar']);

        if ($rows->isEmpty()) {
            return '(no active categories)';
        }

        return $rows
            ->map(fn(Category $category) => sprintf(
                '  %d -> %s%s',
                $category->id,
                (string) ($category->name_en ?? ''),
                filled($category->name_ar) ? ' / '.((string) $category->name_ar) : '',
            ))
            ->implode("\n");
    }
}
