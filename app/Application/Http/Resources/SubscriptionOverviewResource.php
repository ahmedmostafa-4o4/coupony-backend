<?php

namespace App\Application\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionOverviewResource extends JsonResource
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
            'status' => $this->status?->value ?? $this->status,
            'billing_cycle' => $this->billing_cycle?->value ?? $this->billing_cycle,
            'current_period_start' => $this->current_period_start?->toIso8601String(),
            'current_period_end' => $this->current_period_end?->toIso8601String(),
            'grace_period_end' => $this->grace_period_end?->toIso8601String(),
            'degraded_period_end' => $this->degraded_period_end?->toIso8601String(),
            'trial_ends_at' => $this->trial_ends_at?->toIso8601String(),
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'plan' => $this->whenLoaded('plan', function () {
                return [
                    'id' => $this->plan->id,
                    'name' => $this->plan->name,
                    'slug' => $this->plan->slug,
                    'description' => $this->plan->description,
                    'price_monthly' => $this->plan->price_monthly,
                    'price_yearly' => $this->plan->price_yearly,
                    'currency' => $this->plan->currency,
                    'max_products' => $this->plan->max_products,
                    'max_employees' => $this->plan->max_employees,
                    'max_branches' => $this->plan->max_branches,
                    'features' => $this->plan->features,
                ];
            }),
            'usage' => $this->when(isset($this->resource->usage), fn () => $this->resource->usage),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
