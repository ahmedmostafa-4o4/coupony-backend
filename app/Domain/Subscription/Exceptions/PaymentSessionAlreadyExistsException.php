<?php

namespace App\Domain\Subscription\Exceptions;

use RuntimeException;

class PaymentSessionAlreadyExistsException extends RuntimeException
{
    public function __construct(string $storeId)
    {
        parent::__construct(
            "A pending payment session already exists for store '{$storeId}'."
        );
    }

    public static function forStore(string $storeId): self
    {
        return new self($storeId);
    }
}
