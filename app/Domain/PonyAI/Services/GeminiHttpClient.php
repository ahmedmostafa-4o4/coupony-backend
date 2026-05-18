<?php

namespace App\Domain\PonyAI\Services;

use App\Domain\PonyAI\Contracts\GeminiClient;
use App\Domain\PonyAI\DTOs\GeminiResult;
use App\Domain\PonyAI\Exceptions\GeminiException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Throwable;

class GeminiHttpClient implements GeminiClient
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(private readonly array $config) {}

    public function generateText(string $prompt, array $options = []): GeminiResult
    {
        return $this->generate($prompt, $options, jsonMode: false);
    }

    public function generateJson(string $prompt, array $options = []): GeminiResult
    {
        return $this->generate($prompt, $options, jsonMode: true);
    }

    public function embedText(string $text, array $options = []): array
    {
        $model = (string) ($options['model'] ?? $this->config['embed_model']);

        $payload = [
            'content' => [
                'parts' => [['text' => $text]],
            ],
        ];

        if (! empty($options['task_type'])) {
            $payload['taskType'] = (string) $options['task_type'];
        }

        $response = $this->dispatch("/models/{$model}:embedContent", $payload);
        $values = data_get($response, 'embedding.values');

        if (! is_array($values) || $values === []) {
            throw GeminiException::malformedPayload('embedContent response missing embedding.values');
        }

        return array_map(static fn ($value) => (float) $value, $values);
    }

    public function embedImage(string $imageBytes, string $mimeType, array $options = []): array
    {
        $caption = $this->describeImage(
            $imageBytes,
            $mimeType,
            (string) ($options['instruction'] ?? 'Describe this product image for search. Include category, color, materials, brand if visible.'),
            $options,
        );

        $captionText = trim($caption->text);

        if ($captionText === '') {
            throw GeminiException::malformedPayload('Image caption was empty; cannot embed.');
        }

        return $this->embedText($captionText, $options);
    }

    public function describeImage(string $imageBytes, string $mimeType, string $instruction = '', array $options = []): GeminiResult
    {
        $model = (string) ($options['model'] ?? $this->config['vision_model']);
        $promptText = $instruction !== '' ? $instruction : 'Describe this image in one short paragraph.';

        $payload = [
            'contents' => [[
                'parts' => [
                    ['text' => $promptText],
                    [
                        'inline_data' => [
                            'mime_type' => $mimeType,
                            'data' => base64_encode($imageBytes),
                        ],
                    ],
                ],
            ]],
        ];

        $response = $this->dispatch("/models/{$model}:generateContent", $payload);

        return $this->parseGenerateResponse($response, $model);
    }

    /**
     * @param  array<string, mixed>  $options
     */
    private function generate(string $prompt, array $options, bool $jsonMode): GeminiResult
    {
        $model = (string) ($options['model'] ?? $this->config['text_model']);

        $generationConfig = [];
        if ($jsonMode) {
            $generationConfig['response_mime_type'] = 'application/json';
        }
        if (isset($options['temperature'])) {
            $generationConfig['temperature'] = (float) $options['temperature'];
        }
        if (isset($options['max_output_tokens'])) {
            $generationConfig['max_output_tokens'] = (int) $options['max_output_tokens'];
        }

        $payload = [
            'contents' => [[
                'parts' => [['text' => $prompt]],
            ]],
        ];

        if ($generationConfig !== []) {
            $payload['generationConfig'] = $generationConfig;
        }

        if (! empty($options['system_instruction']) && is_string($options['system_instruction'])) {
            $payload['systemInstruction'] = [
                'parts' => [['text' => $options['system_instruction']]],
            ];
        }

        $response = $this->dispatch("/models/{$model}:generateContent", $payload);

        return $this->parseGenerateResponse($response, $model);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function dispatch(string $path, array $payload): array
    {
        $apiKey = (string) ($this->config['api_key'] ?? '');

        if ($apiKey === '') {
            throw GeminiException::missingApiKey();
        }

        $baseUrl = rtrim((string) ($this->config['base_url'] ?? ''), '/');

        try {
            /** @var Response $response */
            $response = Http::baseUrl($baseUrl)
                ->withHeaders(['x-goog-api-key' => $apiKey])
                ->acceptJson()
                ->asJson()
                ->timeout((int) ($this->config['timeout'] ?? 20))
                ->retry((int) ($this->config['retries'] ?? 1), 200, throw: false)
                ->post($path, $payload);
        } catch (ConnectionException $exception) {
            throw GeminiException::transport($exception->getMessage());
        } catch (Throwable $exception) {
            throw GeminiException::transport($exception->getMessage());
        }

        if (! $response->successful()) {
            throw GeminiException::http($response->status(), (string) $response->body());
        }

        $decoded = $response->json();

        if (! is_array($decoded)) {
            throw GeminiException::malformedPayload('non-JSON response body');
        }

        return $decoded;
    }

    /**
     * @param  array<string, mixed>  $response
     */
    private function parseGenerateResponse(array $response, string $model): GeminiResult
    {
        $parts = data_get($response, 'candidates.0.content.parts', []);
        $text = '';

        if (is_array($parts)) {
            foreach ($parts as $part) {
                if (is_array($part) && isset($part['text']) && is_string($part['text'])) {
                    $text .= $part['text'];
                }
            }
        }

        if ($text === '') {
            throw GeminiException::malformedPayload('generateContent response contained no text parts');
        }

        return new GeminiResult(
            text: $text,
            model: $model,
            promptTokens: $this->intOrNull(data_get($response, 'usageMetadata.promptTokenCount')),
            completionTokens: $this->intOrNull(data_get($response, 'usageMetadata.candidatesTokenCount')),
            raw: $response,
        );
    }

    private function intOrNull(mixed $value): ?int
    {
        return is_int($value) || (is_string($value) && ctype_digit($value)) ? (int) $value : null;
    }
}
