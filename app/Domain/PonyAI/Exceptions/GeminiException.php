<?php

namespace App\Domain\PonyAI\Exceptions;

class GeminiException extends PonyAIException
{
    public static function missingApiKey(): self
    {
        return new self('Gemini API key is not configured.');
    }

    public static function transport(string $reason): self
    {
        return new self("Gemini transport error: {$reason}");
    }

    public static function http(int $status, string $body): self
    {
        $snippet = mb_substr($body, 0, 500);

        return new self("Gemini API returned HTTP {$status}: {$snippet}");
    }

    public static function malformedPayload(string $reason): self
    {
        return new self("Gemini returned a malformed payload: {$reason}");
    }
}
