<?php

namespace App\Domain\Subscription\Exceptions;

use Illuminate\Support\Facades\Log;
use RuntimeException;

class PaymobApiException extends RuntimeException
{
    public static function authenticationFailed(string $reason): self
    {
        Log::warning($reason);
        return new self("Paymob authentication failed: {$reason}");
    }

    public static function orderCreationFailed(string $reason): self
    {
                Log::warning($reason);

        return new self("Paymob order creation failed: {$reason}");
    }

    public static function paymentKeyGenerationFailed(string $reason): self
    {        Log::warning($reason);

        return new self("Paymob payment key generation failed: {$reason}");
    }

    public static function networkError(string $reason): self
    {        Log::warning($reason);

        return new self("Paymob network error: {$reason}");
    }

    public static function invalidResponse(int $status, string $body): self
    {
        $snippet = mb_substr($body, 0, 500);

        return new self("Paymob API returned HTTP {$status}: {$snippet}");
    }
}
