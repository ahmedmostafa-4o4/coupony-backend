<?php

namespace App\Domain\Subscription\Exceptions;

use RuntimeException;

class PaymentSessionExpiredException extends RuntimeException
{
    public function __construct(string $sessionId)
    {
        parent::__construct(
            "Payment session '{$sessionId}' has expired."
        );
    }

    public static function make(string $sessionId): self
    {
        return new self($sessionId);
    }
}
