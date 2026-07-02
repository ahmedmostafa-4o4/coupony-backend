<?php

namespace App\Domain\PonyAI\Exceptions;

class AiDailyLimitReachedException extends PonyAIException
{
    /**
     * @param  array{limit: ?int, used: int, remaining: ?int, resets_at: ?string}  $quota
     */
    public function __construct(public readonly array $quota)
    {
        parent::__construct('The daily AI message limit has been reached.');
    }
}
