<?php

namespace App\Domain\Product\Exceptions;

use DomainException;

class OfferClaimLimitException extends DomainException
{
    public function __construct(
        string $message,
        public readonly string $reason
    ) {
        parent::__construct($message);
    }
}
