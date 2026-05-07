<?php

namespace App\Application\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StoreResource extends JsonResource
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
            'description' => $this->description,
            'logo_url' => $this->logo_url ? asset("storage/{$this->logo_url}") : null,
            'banner_url' => $this->banner_url ? asset("storage/{$this->banner_url}") : null,
            'email' => $this->email,
            'phone' => $this->phone,
            'tax_id' => $this->tax_id,
            'status' => $this->status->value,
            'subscription_tier' => $this->subscription_tier,
            'is_verified' => $this->is_verified,
            'verified_at' => $this->verified_at?->toIso8601String(),
            'rating_avg' => $this->rating_avg,
            'rating_count' => $this->rating_count,
            'followers_count' => (int) $this->followers_count,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
            // Verification & approval fields
            'approved_at' => $this->approved_at?->toIso8601String(),
            'approved_by' => $this->approved_by,
            'rejected_at' => $this->rejected_at?->toIso8601String(),
            'rejected_by' => $this->rejected_by,
            'rejection_reason' => $this->rejection_reason,
            'admin_notes' => $this->admin_notes,
            // Relationships
            'owner' => new UserResource($this->whenLoaded('owner')),
            'categories' => StoreCategoryResource::collection($this->whenLoaded('categories')),
            'addresses' => StoreAddressResource::collection($this->whenLoaded('addresses')),
            'verifications' => VerificationResource::collection($this->whenLoaded('verifications')),
            'socials' => StoreSocialResource::collection($this->whenLoaded('socials')),
            'hours' => $this->whenLoaded('hours'),
        ];
    }
}
