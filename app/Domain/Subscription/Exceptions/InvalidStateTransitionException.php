<?php

namespace App\Domain\Subscription\Exceptions;

use App\Domain\Subscription\Enums\SubscriptionStatus;
use RuntimeException;

class InvalidStateTransitionException extends RuntimeException
{
    private SubscriptionStatus $from;

    private SubscriptionStatus $to;

    public function __construct(SubscriptionStatus $from, SubscriptionStatus $to)
    {
        $this->from = $from;
        $this->to = $to;

        parent::__construct(
            "Invalid subscription state transition from '{$from->value}' to '{$to->value}'."
        );
    }

    public static function make(SubscriptionStatus $from, SubscriptionStatus $to): self
    {
        return new self($from, $to);
    }

    public function getFrom(): SubscriptionStatus
    {
        return $this->from;
    }

    public function getTo(): SubscriptionStatus
    {
        return $this->to;
    }
}
