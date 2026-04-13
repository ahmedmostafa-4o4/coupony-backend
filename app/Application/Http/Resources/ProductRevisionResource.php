<?php

namespace App\Application\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductRevisionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'revision_no' => $this->revision_no,
            'action' => $this->action?->value ?? $this->action,
            'status' => $this->status?->value ?? $this->status,
            'base_revision_no' => $this->base_revision_no,
            'submitted_by' => $this->submitted_by,
            'submitted_at' => $this->submitted_at?->toIso8601String(),
            'reviewed_by' => $this->reviewed_by,
            'reviewed_at' => $this->reviewed_at?->toIso8601String(),
            'rejection_reason' => $this->rejection_reason,
            'admin_notes' => $this->admin_notes,
            'payload' => $this->payload,
            'product' => $this->whenLoaded('product', function () {
                return [
                    'id' => $this->product?->id,
                    'title' => $this->product?->title,
                    'slug' => $this->product?->slug,
                ];
            }),
        ];
    }
}
