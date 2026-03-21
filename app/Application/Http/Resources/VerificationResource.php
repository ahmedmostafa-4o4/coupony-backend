<?php

namespace App\Application\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VerificationResource extends JsonResource
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
            'store_id' => $this->store_id,

            'document_type' => $this->document_type,
            'document_path' => $this->document_path,

            'status' => $this->status,

            'reviewed_by' => $this->reviewed_by,
            'reviewed_at' => $this->reviewed_at,

            'rejection_reason' => $this->rejection_reason,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];

    }
}
