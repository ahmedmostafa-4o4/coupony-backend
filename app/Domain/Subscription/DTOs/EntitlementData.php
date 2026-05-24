<?php

namespace App\Domain\Subscription\DTOs;

final class EntitlementData
{
    /**
     * @param  array<string, array{limit: int, usage: int, remaining: int}>  $limits
     * @param  array<string, bool>  $features
     */
    public function __construct(
        public readonly array $limits,
        public readonly array $features,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'limits' => $this->limits,
            'features' => $this->features,
        ];
    }
}
