<?php

namespace App\Application\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BannerClaimResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'banner_id' => $this->banner_id,
            'user_id' => $this->user_id,
            'store_id' => $this->store_id,
            'status' => $this->status?->value ?? $this->status,
            'claim_token' => $this->claim_token,
            'qr_code_token' => $this->qr_code_token,
            'claim_snapshot' => $this->claim_snapshot,
            'expires_at' => $this->expires_at?->toIso8601String(),
            'redeemed_at' => $this->redeemed_at?->toIso8601String(),
            'redeemed_by' => $this->redeemed_by,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
