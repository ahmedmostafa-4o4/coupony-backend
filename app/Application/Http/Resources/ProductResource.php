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
            'product_type' => $this->product_type?->value ?? $this->product_type,
            'base_price' => $this->base_price,
            'compare_at_price' => $this->compare_at_price,
            'currency' => $this->currency,
            'sku' => $this->sku,
            'status' => $this->status?->value ?? $this->status,
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
            'store' => $this->whenLoaded('store', function () {
                return [
                    'id' => $this->store?->id,
                    'name' => $this->store?->name,
                ];
            }),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
