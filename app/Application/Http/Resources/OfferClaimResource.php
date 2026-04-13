<?php

namespace App\Application\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OfferClaimResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'store_id' => $this->store_id,
            'product_id' => $this->product_id,
            'offer_id' => $this->offer_id,
            'status' => $this->status?->value ?? $this->status,
            'claim_token' => $this->claim_token,
            'qr_code_token' => $this->qr_code_token,
            'offer_snapshot' => $this->offer_snapshot,
            'expires_at' => $this->expires_at?->toIso8601String(),
            'redeemed_at' => $this->redeemed_at?->toIso8601String(),
            'redeemed_by' => $this->redeemed_by,
            'is_expired' => $this->isExpired(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
