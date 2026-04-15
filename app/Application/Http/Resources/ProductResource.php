<?php

namespace App\Application\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
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
            'title' => $this->title,
            'slug' => $this->slug,
            'short_description' => $this->short_description,
            'description' => $this->description,
            'currency' => $this->currency,
            'status' => $this->status?->value ?? $this->status,
            'approval_status' => $this->approval_status?->value ?? $this->approval_status,
            'is_featured' => $this->is_featured,
            'sale_count' => $this->sale_count,
            'redemption_count' => $this->redemption_count,
            'primary_image_url' => $this->whenLoaded('images', function () {
                $primaryImage = $this->images->firstWhere('is_primary', true) ?? $this->images->first();

                if (!$primaryImage) {
                    return null;
                }

                return ProductImageResource::make($primaryImage)->resolve()['url'];
            }),
            'categories' => CategoryResource::collection($this->whenLoaded('categories')),
            'images' => ProductImageResource::collection($this->whenLoaded('images')),
            'variants' => ProductVariantResource::collection($this->whenLoaded('variants')),
            'offer' => ProductOfferResource::make($this->whenLoaded('offer')),
            'store' => $this->whenLoaded('store', function () {
                return [
                    'id' => $this->store?->id,
                    'name' => $this->store?->name,
                ];
            }),
            'pending_revision' => $this->whenLoaded('pendingRevision', function () {
                return $this->pendingRevision
                    ? ProductRevisionResource::make($this->pendingRevision)->resolve()
                    : null;
            }),
        ];
    }
}
