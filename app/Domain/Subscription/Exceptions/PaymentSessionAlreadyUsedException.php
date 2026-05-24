<?php

namespace App\Domain\Subscription\Exceptions;

use RuntimeException;

class PaymentSessionAlreadyUsedException extends RuntimeException
{
    public function __construct(string $sessionId)
    {
        parent::__construct(
            "Payment session '{$sessionId}' has already been used."
        );
    }

    public static function make(string $sessionId): self
    {
        return new self($sessionId);
    }
}
