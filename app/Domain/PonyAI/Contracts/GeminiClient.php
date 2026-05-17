<?php

namespace App\Domain\PonyAI\Contracts;

use App\Domain\PonyAI\DTOs\GeminiResult;

interface GeminiClient
{
    /**
     * Generate free-form text from a prompt.
     *
     * @param  array<string, mixed>  $options
     */
    public function generateText(string $prompt, array $options = []): GeminiResult;

    /**
     * Generate a JSON-mode response. The returned GeminiResult's text is a JSON string;
     * callers should use GeminiResult::decodeJson() to get an array.
     *
     * @param  array<string, mixed>  $options
     */
    public function generateJson(string $prompt, array $options = []): GeminiResult;

    /**
     * Embed a piece of text and return its vector.
     *
     * @return array<int, float>
     */
    public function embedText(string $text, array $options = []): array;

    /**
     * Embed an image (raw bytes + mime) and return its vector.
     *
     * @return array<int, float>
     */
    public function embedImage(string $imageBytes, string $mimeType, array $options = []): array;

    /**
     * Ask the vision model to describe an image. Returns a textual caption.
     *
     * @param  array<string, mixed>  $options
     */
    public function describeImage(string $imageBytes, string $mimeType, string $instruction = '', array $options = []): GeminiResult;
}
