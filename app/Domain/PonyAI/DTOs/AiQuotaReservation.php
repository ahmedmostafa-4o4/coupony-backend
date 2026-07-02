<?php

namespace App\Domain\PonyAI\DTOs;

use Carbon\CarbonImmutable;

final class AiQuotaReservation
{
    /**
     * @param  array{limit: ?int, used: int, remaining: ?int, resets_at: ?string}  $quota
     */
    public function __construct(
        public readonly string $subjectType,
        public readonly string $subjectId,
        public readonly CarbonImmutable $usageDate,
        public readonly string $reservationToken,
        public readonly bool $reserved,
        public readonly array $quota,
    ) {}
}
