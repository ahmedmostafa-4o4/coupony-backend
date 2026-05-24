<?php

namespace App\Domain\Subscription\Exceptions;

use RuntimeException;

class PaymentSessionNotFoundException extends RuntimeException
{
    public function __construct(string $sessionId)
    {
        parent::__construct(
            "Payment session '{$sessionId}' not found."
        );
    }

    public static function make(string $sessionId): self
    {
        return new self($sessionId);
    }
}
