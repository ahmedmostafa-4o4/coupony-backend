<?php

namespace App\Application\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StorePointsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'current_balance' => (int) $this->current_balance,
            'lifetime_earned' => (int) $this->lifetime_earned,
            'lifetime_spent' => (int) $this->lifetime_spent,
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
