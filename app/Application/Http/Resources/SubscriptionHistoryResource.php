<?php

namespace App\Application\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionHistoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'plan_name' => $this->whenLoaded('plan', fn () => $this->plan?->name),
            'billing_cycle' => $this->billing_cycle?->value ?? $this->billing_cycle,
            'amount' => $this->amount,
            'payment_method' => $this->payment_method,
            'status' => $this->status?->value ?? $this->status,
            'period_start' => $this->period_start?->toIso8601String(),
            'period_end' => $this->period_end?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
