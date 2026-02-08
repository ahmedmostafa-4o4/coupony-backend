<?php

namespace App\Application\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Log;

class StoreResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        Log::info('StoreAddressResource');
        return [
            'id' => $this->id,
            'ss' => $this->status,
            'name' => $this->name,
            'description' => $this->description,
            'owner_user_id' => $this->owner_user_id,
            'addresses' => StoreAddressResource::collection($this->whenLoaded('addresses')),
            'categories' => StoreCategoryResource::collection($this->whenLoaded('categories')),
            'status' => $this->status,
            'phone' => $this->phone,
            'email' => $this->email,
            'verifications' => $this->whenLoaded('verifications'),
            'logo_url' => $this->logo_url,
            'banner_url' => $this->banner_url,
            'subscription_tier' => $this->subscription_tier,
            'commission_rate' => $this->commission_rate,
            'tax_id' => $this->tax_id,
            'is_verified' => $this->is_verified,
            'verified_at' => $this->verified_at,
            'total_sales' => $this->total_sales,
            'rating_avg' => $this->rating_avg,
            'rating_count' => $this->rating_count,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}
