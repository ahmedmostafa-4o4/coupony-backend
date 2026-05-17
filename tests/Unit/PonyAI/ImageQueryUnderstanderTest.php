<?php

namespace Tests\Unit\PonyAI;

use App\Domain\PonyAI\Contracts\GeminiClient;
use App\Domain\PonyAI\DTOs\GeminiResult;
use App\Domain\PonyAI\Exceptions\GeminiException;
use App\Domain\PonyAI\Services\GeminiFakeClient;
use App\Domain\PonyAI\Services\Pipeline\ImageQueryUnderstander;
use Tests\TestCase;

class ImageQueryUnderstanderTest extends TestCase
{
    private function fake(): GeminiFakeClient
    {
        /** @var GeminiFakeClient $client */
        $client = $this->app->make(GeminiClient::class);

        return $client;
    }

    public function test_understands_well_formed_json_payload(): void
    {
        $payload = [
            'caption' => 'A red leather bifold wallet on a wooden table.',
            'category_guess' => 'Wallet',
            'color' => 'Red',
            'attributes' => ['Bifold', 'leather', 'leather', ' '],
        ];

        $this->fake()->queueDescription(json_encode($payload, JSON_UNESCAPED_UNICODE));

        $result = $this->app->make(ImageQueryUnderstander::class)
            ->understand("\xFF\xD8\xFF\xE0image-bytes", 'image/jpeg');

        $this->assertSame('A red leather bifold wallet on a wooden table.', $result->caption);
        $this->assertSame('Wallet', $result->categoryGuess);
        $this->assertSame('Red', $result->color);
        $this->assertSame(['bifold', 'leather'], $result->attributes);
    }

    public function test_strips_markdown_code_fences_from_payload(): void
    {
        $this->fake()->queueDescription("```json\n".json_encode(['caption' => 'sneakers']).'```');

        $result = $this->app->make(ImageQueryUnderstander::class)
            ->understand('bytes', 'image/png');

        $this->assertSame('sneakers', $result->caption);
    }

    public function test_falls_back_to_raw_text_when_payload_is_not_json(): void
    {
        $this->fake()->queueDescription('just a sentence, not json');

        $result = $this->app->make(ImageQueryUnderstander::class)
            ->understand('bytes', 'image/png');

        $this->assertSame('just a sentence, not json', $result->caption);
        $this->assertNull($result->categoryGuess);
        $this->assertSame([], $result->attributes);
    }

    public function test_gemini_failure_returns_empty_understanding(): void
    {
        $throwing = new class implements GeminiClient {
            public function generateText(string $p, array $o = []): GeminiResult { throw new GeminiException('x'); }
            public function generateJson(string $p, array $o = []): GeminiResult { throw new GeminiException('x'); }
            public function embedText(string $t, array $o = []): array { throw new GeminiException('x'); }
            public function embedImage(string $b, string $m, array $o = []): array { throw new GeminiException('x'); }
            public function describeImage(string $b, string $m, string $i = '', array $o = []): GeminiResult { throw new GeminiException('x'); }
        };

        $this->app->instance(GeminiClient::class, $throwing);

        $result = $this->app->make(ImageQueryUnderstander::class)
            ->understand('bytes', 'image/jpeg');

        $this->assertSame('', $result->caption);
        $this->assertSame([], $result->attributes);
    }

    public function test_to_intent_combines_caption_attributes_color_and_category(): void
    {
        $this->fake()->queueDescription(json_encode([
            'caption' => 'red leather wallet',
            'category_guess' => 'wallets',
            'color' => 'red',
            'attributes' => ['leather', 'bifold'],
        ]));

        $understanding = $this->app->make(ImageQueryUnderstander::class)
            ->understand('bytes', 'image/jpeg');

        $intent = $understanding->toIntent('something nice');

        $this->assertSame('red leather wallet something nice', $intent->freeText);
        $this->assertEqualsCanonicalizing(['leather', 'bifold', 'red', 'wallets'], $intent->attributes);
    }

    public function test_to_intent_works_with_empty_extra_message(): void
    {
        $this->fake()->queueDescription(json_encode(['caption' => 'hat']));

        $understanding = $this->app->make(ImageQueryUnderstander::class)
            ->understand('bytes', 'image/jpeg');

        $intent = $understanding->toIntent('');

        $this->assertSame('hat', $intent->freeText);
        $this->assertSame([], $intent->attributes);
    }
}
