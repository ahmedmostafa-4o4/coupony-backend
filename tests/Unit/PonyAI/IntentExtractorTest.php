<?php

namespace Tests\Unit\PonyAI;

use App\Domain\PonyAI\Contracts\GeminiClient;
use App\Domain\PonyAI\Services\GeminiFakeClient;
use App\Domain\PonyAI\Services\Pipeline\IntentExtractor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IntentExtractorTest extends TestCase
{
    use RefreshDatabase;

    private function fake(): GeminiFakeClient
    {
        /** @var GeminiFakeClient $client */
        $client = $this->app->make(GeminiClient::class);

        return $client;
    }

    public function test_extracts_category_price_band_and_attributes(): void
    {
        $this->fake()->queueJson([
            'category_id' => 12,
            'price_min' => 50,
            'price_max' => 250,
            'attributes' => ['Red', 'leather', 'leather', ''],
        ]);

        $intent = $this->app->make(IntentExtractor::class)->extract('cheap red leather wallet under 250');

        $this->assertSame('cheap red leather wallet under 250', $intent->freeText);
        $this->assertSame(12, $intent->categoryId);
        $this->assertSame(50.0, $intent->priceMin);
        $this->assertSame(250.0, $intent->priceMax);
        $this->assertSame(['red', 'leather'], $intent->attributes);
    }

    public function test_swaps_inverted_price_band(): void
    {
        $this->fake()->queueJson([
            'price_min' => 300,
            'price_max' => 100,
        ]);

        $intent = $this->app->make(IntentExtractor::class)->extract('anything');

        $this->assertSame(100.0, $intent->priceMin);
        $this->assertSame(300.0, $intent->priceMax);
    }

    public function test_drops_negative_prices(): void
    {
        $this->fake()->queueJson([
            'price_min' => -5,
            'price_max' => 'not a number',
        ]);

        $intent = $this->app->make(IntentExtractor::class)->extract('hi');

        $this->assertNull($intent->priceMin);
        $this->assertNull($intent->priceMax);
    }

    public function test_empty_prompt_returns_empty_intent(): void
    {
        $intent = $this->app->make(IntentExtractor::class)->extract('   ');

        $this->assertSame('', $intent->freeText);
        $this->assertNull($intent->categoryId);
        $this->assertSame([], $intent->attributes);
    }

    public function test_gemini_failure_degrades_to_free_text_intent(): void
    {
        // No queued response and we replace the fake with a throwing stub via the container.
        $throwing = new class implements GeminiClient
        {
            public function generateText(string $prompt, array $options = []): \App\Domain\PonyAI\DTOs\GeminiResult
            {
                throw new \App\Domain\PonyAI\Exceptions\GeminiException('boom');
            }

            public function generateJson(string $prompt, array $options = []): \App\Domain\PonyAI\DTOs\GeminiResult
            {
                throw new \App\Domain\PonyAI\Exceptions\GeminiException('boom');
            }

            public function embedText(string $text, array $options = []): array
            {
                return [];
            }

            public function embedImage(string $imageBytes, string $mimeType, array $options = []): array
            {
                return [];
            }

            public function describeImage(string $imageBytes, string $mimeType, string $instruction = '', array $options = []): \App\Domain\PonyAI\DTOs\GeminiResult
            {
                throw new \App\Domain\PonyAI\Exceptions\GeminiException('boom');
            }
        };

        $this->app->instance(GeminiClient::class, $throwing);

        $intent = $this->app->make(IntentExtractor::class)->extract('hello world');

        $this->assertSame('hello world', $intent->freeText);
        $this->assertNull($intent->categoryId);
        $this->assertNull($intent->priceMin);
    }

    public function test_payload_without_expected_keys_returns_safe_intent(): void
    {
        $this->fake()->queueJson(['unrelated' => true]);

        $intent = $this->app->make(IntentExtractor::class)->extract('hello');

        $this->assertSame('hello', $intent->freeText);
        $this->assertSame([], $intent->attributes);
        $this->assertNull($intent->categoryId);
        $this->assertNull($intent->priceMin);
        $this->assertNull($intent->priceMax);
    }
}
