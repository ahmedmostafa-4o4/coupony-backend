<?php

namespace App\Application\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StorePointTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'points' => (int) $this->points,
            'balance_before' => (int) $this->balance_before,
            'balance_after' => (int) $this->balance_after,
            'reason' => $this->reason,
            'note' => $this->note,
            'meta' => $this->meta,
            'admin_user_id' => $this->admin_user_id,
            'store_id' => $this->store_id,
            'user_id' => $this->user_id,
            'offer_claim_id' => $this->offer_claim_id,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
