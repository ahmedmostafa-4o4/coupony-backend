<?php

namespace App\Application\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicProductResource extends JsonResource
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
            'short_description' => $this->short_description,
            'description' => $this->description,
            'currency' => $this->currency,
            'base_price' => $this->base_price,
            'compare_at_price' => $this->compare_at_price,
            'is_featured' => $this->is_featured,
            'sale_count' => $this->sale_count,
            'redemption_count' => $this->redemption_count,
            'rating_avg' => (float) ($this->rating_avg ?? 0),
            'rating_count' => (int) ($this->rating_count ?? 0),
            'comments_count' => (int) ($this->comments_count ?? 0),
            'likes_count' => (int) ($this->likes_count ?? 0),
            'views_count' => (int) ($this->views_count ?? 0),
            'unique_viewers_count' => (int) ($this->unique_viewers_count ?? 0),
            'is_liked' => (bool) ($this->is_liked ?? false),
            'is_favorited' => (bool) ($this->is_favorited ?? false),
            'primary_image_url' => $this->whenLoaded('images', function () {
                $primaryImage = $this->images->firstWhere('is_primary', true) ?? $this->images->first();

                if (!$primaryImage) {
                    return null;
                }

                return ProductImageResource::make($primaryImage)->resolve()['url'];
            }),
            'offer' => $this->whenLoaded('offer', function () {
                return [
                    'id' => $this->offer?->id,
                    'type' => $this->offer?->type,
                    'label' => $this->offer?->label,
                    'fixed_amount' => $this->offer?->fixed_amount,
                    'percentage_value' => $this->offer?->percentage_value,
                    'buy_qty' => $this->offer?->buy_qty,
                    'get_qty' => $this->offer?->get_qty,
                ];
            }),
            'store' => $this->whenLoaded('store', function () {
                return [
                    'id' => $this->store?->id,
                    'name' => $this->store?->name,
                ];
            }),
        ];
    }
}
