<?php

namespace App\Notifications\Admin;

use App\Domain\Store\Models\Store;

class StoreLimitReachedNotification extends AdminNotification
{
    public function __construct(
        public Store $store,
        public string $limitType,
        public int $currentValue,
        public int $maxValue
    ) {}

    public function toDatabase($notifiable): array
    {
        return [
            'title' => 'Store Limit Reached',
            'message' => "Store '{$this->store->name}' has reached its limit for {$this->limitType} ({$this->currentValue}/{$this->maxValue}).",
            'reference_type' => Store::class,
            'reference_id' => $this->store->id,
            'data' => [
                'store_id' => $this->store->id,
                'limit_type' => $this->limitType,
                'current_value' => $this->currentValue,
                'max_value' => $this->maxValue,
            ]
        ];
    }
}
