<?php

namespace App\Domain\PonyAI\Services;

use App\Domain\PonyAI\Contracts\GeminiClient;
use App\Domain\PonyAI\DTOs\GeminiResult;

/**
 * Deterministic in-memory implementation of the GeminiClient contract.
 *
 * Used by tests and by local environments that opt into GEMINI_FAKE=true.
 * Callers can pre-load scripted responses (queueText / queueJson / etc.);
 * if the queue for a given method is empty the client falls back to a
 * deterministic synthetic response so tests remain useful without
 * having to script every single call.
 */
class GeminiFakeClient implements GeminiClient
{
    /** @var array<int, GeminiResult> */
    private array $textQueue = [];

    /** @var array<int, GeminiResult> */
    private array $jsonQueue = [];

    /** @var array<int, array<int, float>> */
    private array $embedQueue = [];

    /** @var array<int, GeminiResult> */
    private array $describeQueue = [];

    /** @var array<int, array{method: string, prompt?: string, instruction?: string, mime?: string, bytes_length?: int, options: array<string, mixed>}> */
    public array $calls = [];

    public function queueText(string $text, ?string $model = null): self
    {
        $this->textQueue[] = new GeminiResult(text: $text, model: $model);

        return $this;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function queueJson(array $payload, ?string $model = null): self
    {
        $this->jsonQueue[] = new GeminiResult(
            text: json_encode($payload, JSON_UNESCAPED_UNICODE) ?: '{}',
            model: $model,
        );

        return $this;
    }

    /**
     * @param  array<int, float>  $vector
     */
    public function queueEmbedding(array $vector): self
    {
        $this->embedQueue[] = array_map(static fn ($value) => (float) $value, $vector);

        return $this;
    }

    public function queueDescription(string $caption, ?string $model = null): self
    {
        $this->describeQueue[] = new GeminiResult(text: $caption, model: $model);

        return $this;
    }

    public function generateText(string $prompt, array $options = []): GeminiResult
    {
        $this->calls[] = ['method' => 'generateText', 'prompt' => $prompt, 'options' => $options];

        return array_shift($this->textQueue)
            ?? new GeminiResult(text: "fake-text:{$this->shortHash($prompt)}");
    }

    public function generateJson(string $prompt, array $options = []): GeminiResult
    {
        $this->calls[] = ['method' => 'generateJson', 'prompt' => $prompt, 'options' => $options];

        return array_shift($this->jsonQueue)
            ?? new GeminiResult(text: json_encode(['echo' => $this->shortHash($prompt)]) ?: '{}');
    }

    public function embedText(string $text, array $options = []): array
    {
        $this->calls[] = ['method' => 'embedText', 'prompt' => $text, 'options' => $options];

        return array_shift($this->embedQueue) ?? $this->deterministicVector($text);
    }

    public function embedImage(string $imageBytes, string $mimeType, array $options = []): array
    {
        $this->calls[] = [
            'method' => 'embedImage',
            'mime' => $mimeType,
            'bytes_length' => strlen($imageBytes),
            'options' => $options,
        ];

        return array_shift($this->embedQueue) ?? $this->deterministicVector($mimeType.':'.strlen($imageBytes));
    }

    public function describeImage(string $imageBytes, string $mimeType, string $instruction = '', array $options = []): GeminiResult
    {
        $this->calls[] = [
            'method' => 'describeImage',
            'instruction' => $instruction,
            'mime' => $mimeType,
            'bytes_length' => strlen($imageBytes),
            'options' => $options,
        ];

        return array_shift($this->describeQueue)
            ?? new GeminiResult(text: "fake-caption:{$mimeType}:".strlen($imageBytes));
    }

    private function shortHash(string $input): string
    {
        return substr(hash('sha256', $input), 0, 12);
    }

    /**
     * @return array<int, float>
     */
    private function deterministicVector(string $seed): array
    {
        $bytes = hash('sha256', $seed, true);
        $vector = [];

        for ($i = 0; $i < 16; $i++) {
            $vector[] = (ord($bytes[$i]) / 255.0) * 2.0 - 1.0;
        }

        return $vector;
    }
}
