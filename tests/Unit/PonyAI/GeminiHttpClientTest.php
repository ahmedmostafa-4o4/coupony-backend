<?php

namespace Tests\Unit\PonyAI;

use App\Domain\PonyAI\Exceptions\GeminiException;
use App\Domain\PonyAI\Services\GeminiHttpClient;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GeminiHttpClientTest extends TestCase
{
    /**
     * @return array<string, mixed>
     */
    private function config(array $overrides = []): array
    {
        return array_merge([
            'api_key' => 'test-key',
            'base_url' => 'https://gemini.test/v1beta',
            'text_model' => 'gemini-2.5-flash',
            'vision_model' => 'gemini-2.5-flash',
            'embed_model' => 'text-embedding-004',
            'timeout' => 5,
            'retries' => 1,
        ], $overrides);
    }

    public function test_generate_text_parses_candidates_and_usage(): void
    {
        Http::fake([
            'gemini.test/v1beta/models/gemini-2.5-flash:generateContent' => Http::response([
                'candidates' => [[
                    'content' => [
                        'parts' => [
                            ['text' => 'hello '],
                            ['text' => 'world'],
                        ],
                    ],
                ]],
                'usageMetadata' => [
                    'promptTokenCount' => 12,
                    'candidatesTokenCount' => 7,
                ],
            ], 200),
        ]);

        $client = new GeminiHttpClient($this->config());
        $result = $client->generateText('Hi there', ['temperature' => 0.2, 'max_output_tokens' => 64]);

        $this->assertSame('hello world', $result->text);
        $this->assertSame('gemini-2.5-flash', $result->model);
        $this->assertSame(12, $result->promptTokens);
        $this->assertSame(7, $result->completionTokens);

        Http::assertSent(function (Request $request): bool {
            $body = $request->data();

            return $request->method() === 'POST'
                && $request->url() === 'https://gemini.test/v1beta/models/gemini-2.5-flash:generateContent'
                && $request->header('x-goog-api-key') === ['test-key']
                && data_get($body, 'contents.0.parts.0.text') === 'Hi there'
                && data_get($body, 'generationConfig.temperature') === 0.2
                && data_get($body, 'generationConfig.max_output_tokens') === 64
                && data_get($body, 'generationConfig.response_mime_type') === null;
        });
    }

    public function test_generate_text_does_not_expose_api_key_in_url_or_body(): void
    {
        Http::fake([
            'gemini.test/*' => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => 'ok']]]]],
            ], 200),
        ]);

        $client = new GeminiHttpClient($this->config(['api_key' => 'super-secret-key']));
        $client->generateText('ping');

        Http::assertSent(function (Request $request): bool {
            $rawBody = (string) $request->body();
            $url = $request->url();

            return ! str_contains($url, 'super-secret-key')
                && ! str_contains($rawBody, 'super-secret-key')
                && $request->header('x-goog-api-key') === ['super-secret-key'];
        });
    }

    public function test_generate_json_sets_response_mime_type_and_decodes(): void
    {
        Http::fake([
            'gemini.test/*' => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => '{"intent":"search","price_max":100}']]]]],
            ], 200),
        ]);

        $client = new GeminiHttpClient($this->config());
        $result = $client->generateJson('Extract intent');

        $this->assertSame(['intent' => 'search', 'price_max' => 100], $result->decodeJson());

        Http::assertSent(function (Request $request): bool {
            return data_get($request->data(), 'generationConfig.response_mime_type') === 'application/json';
        });
    }

    public function test_generate_json_decode_strips_markdown_code_fences(): void
    {
        Http::fake([
            'gemini.test/*' => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => "```json\n{\"ok\":true}\n```"]]]]],
            ], 200),
        ]);

        $client = new GeminiHttpClient($this->config());
        $this->assertSame(['ok' => true], $client->generateJson('x')->decodeJson());
    }

    public function test_embed_text_returns_float_vector(): void
    {
        Http::fake([
            'gemini.test/v1beta/models/text-embedding-004:embedContent' => Http::response([
                'embedding' => ['values' => [0.1, 0.2, '0.3', -0.4]],
            ], 200),
        ]);

        $client = new GeminiHttpClient($this->config());
        $vector = $client->embedText('coupony product', ['task_type' => 'RETRIEVAL_DOCUMENT']);

        $this->assertSame([0.1, 0.2, 0.3, -0.4], $vector);

        Http::assertSent(function (Request $request): bool {
            return data_get($request->data(), 'content.parts.0.text') === 'coupony product'
                && data_get($request->data(), 'taskType') === 'RETRIEVAL_DOCUMENT';
        });
    }

    public function test_describe_image_sends_inline_base64_payload(): void
    {
        Http::fake([
            'gemini.test/*' => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => 'a red sneaker']]]]],
            ], 200),
        ]);

        $client = new GeminiHttpClient($this->config());
        $bytes = "\x89PNG\r\n\x1a\nfakebytes";
        $result = $client->describeImage($bytes, 'image/png', 'Describe the product.');

        $this->assertSame('a red sneaker', $result->text);

        Http::assertSent(function (Request $request) use ($bytes): bool {
            $payload = $request->data();
            $inline = data_get($payload, 'contents.0.parts.1.inline_data');

            return is_array($inline)
                && $inline['mime_type'] === 'image/png'
                && $inline['data'] === base64_encode($bytes)
                && data_get($payload, 'contents.0.parts.0.text') === 'Describe the product.';
        });
    }

    public function test_embed_image_uses_caption_then_embed(): void
    {
        Http::fake([
            'gemini.test/v1beta/models/gemini-2.5-flash:generateContent' => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => 'blue mug ceramic']]]]],
            ], 200),
            'gemini.test/v1beta/models/text-embedding-004:embedContent' => Http::response([
                'embedding' => ['values' => [0.5, 0.5]],
            ], 200),
        ]);

        $client = new GeminiHttpClient($this->config());
        $vector = $client->embedImage('bytes', 'image/jpeg');

        $this->assertSame([0.5, 0.5], $vector);

        Http::assertSentCount(2);
        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), ':generateContent'));
        Http::assertSent(function (Request $request): bool {
            return str_contains($request->url(), ':embedContent')
                && data_get($request->data(), 'content.parts.0.text') === 'blue mug ceramic';
        });
    }

    public function test_missing_api_key_throws(): void
    {
        Http::fake();

        $client = new GeminiHttpClient($this->config(['api_key' => '']));

        $this->expectException(GeminiException::class);
        $this->expectExceptionMessage('Gemini API key is not configured.');

        $client->generateText('hi');
    }

    public function test_http_error_status_throws_with_status_in_message(): void
    {
        Http::fake([
            'gemini.test/*' => Http::response(['error' => ['message' => 'invalid model']], 400),
        ]);

        $client = new GeminiHttpClient($this->config());

        try {
            $client->generateText('hi');
            $this->fail('Expected GeminiException');
        } catch (GeminiException $exception) {
            $this->assertStringContainsString('HTTP 400', $exception->getMessage());
            $this->assertStringContainsString('invalid model', $exception->getMessage());
        }
    }

    public function test_malformed_response_throws(): void
    {
        Http::fake([
            'gemini.test/*' => Http::response(['candidates' => []], 200),
        ]);

        $client = new GeminiHttpClient($this->config());

        $this->expectException(GeminiException::class);
        $this->expectExceptionMessage('no text parts');

        $client->generateText('hi');
    }

    public function test_embed_text_throws_when_values_missing(): void
    {
        Http::fake([
            'gemini.test/*' => Http::response(['embedding' => []], 200),
        ]);

        $client = new GeminiHttpClient($this->config());

        $this->expectException(GeminiException::class);
        $this->expectExceptionMessage('embedding.values');

        $client->embedText('hi');
    }
}
