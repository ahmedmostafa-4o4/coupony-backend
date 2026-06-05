<?php

namespace App\Domain\Store\Events;

use App\Domain\Store\Models\Store;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StoreLimitReached
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public Store $store,
        public string $limitType, // e.g. 'products', 'employees'
        public int $currentValue,
        public int $maxValue
    ) {}
}
