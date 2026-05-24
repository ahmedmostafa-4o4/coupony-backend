<?php

namespace App\Application\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionPlanResource extends JsonResource
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
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'prices' => [
                'monthly' => $this->price_monthly,
                'yearly' => $this->price_yearly,
                'currency' => $this->currency,
            ],
            'entitlements' => [
                'max_products' => $this->max_products,
                'max_employees' => $this->max_employees,
                'max_branches' => $this->max_branches,
            ],
            'features' => $this->features,
            'payment_config' => [
                'is_review_mode' => config('subscription.is_review_mode', false),
                'supported_payment_methods' => config('subscription.supported_payment_methods', ['card', 'wallet']),
            ],
        ];
    }
}
