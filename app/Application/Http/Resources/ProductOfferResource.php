<?php

namespace App\Application\Http\Resources;

use App\Domain\Product\Enums\ProductOfferTargetRole;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductOfferResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $targets = $this->whenLoaded('targets', fn () => $this->targets);

        return [
            'id' => $this->id,
            'type' => $this->type?->value ?? $this->type,
            'status' => $this->status?->value ?? $this->status,
            'label' => $this->label,
            'starts_at' => $this->starts_at?->toIso8601String(),
            'ends_at' => $this->ends_at?->toIso8601String(),
            'claim_expiration_minutes' => $this->claim_expiration_minutes,
            'fixed_amount' => $this->fixed_amount,
            'percentage_value' => $this->percentage_value,
            'max_discount' => $this->max_discount,
            'buy_qty' => $this->buy_qty,
            'get_qty' => $this->get_qty,
            'allow_mix_buy_variants' => $this->allow_mix_buy_variants,
            'allow_mix_reward_variants' => $this->allow_mix_reward_variants,
            'buy_variant_ids' => $this->whenLoaded('targets', function () use ($targets) {
                return $targets
                    ->filter(fn ($target) => ($target->role?->value ?? $target->role) === ProductOfferTargetRole::BUY->value)
                    ->pluck('variant_id')
                    ->values()
                    ->all();
            }, []),
            'reward_variant_ids' => $this->whenLoaded('targets', function () use ($targets) {
                return $targets
                    ->filter(fn ($target) => ($target->role?->value ?? $target->role) === ProductOfferTargetRole::REWARD->value)
                    ->pluck('variant_id')
                    ->values()
                    ->all();
            }, []),
        ];
    }
}
