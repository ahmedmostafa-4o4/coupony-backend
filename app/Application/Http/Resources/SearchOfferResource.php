<?php

namespace App\Application\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SearchOfferResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Distance calculation relies on 'distance' alias injected by DB query
        $distance = $this->distance ?? null;

        $categoryId = $this->categories->first()?->id;
        
        $discountedPrice = $this->base_price; // Or whatever calculation represents the actual offer price if it's an offer

        return [
            'id' => $this->id,
            'product_id' => $this->id,
            'store_id' => $this->store_id,
            'image_url' => $this->images->first()?->image_url,
            'title' => $this->title,
            'store_name' => $this->store?->name,
            'original_price' => (float) $this->compare_at_price > 0 ? (float) $this->compare_at_price : (float) $this->base_price,
            'discounted_price' => (float) $this->base_price,
            'discount_percent' => $this->calculateDiscountPercent((float) $this->base_price, (float) $this->compare_at_price),
            'rating' => (float) $this->rating_avg,
            'is_favorite' => $this->is_favorited ?? false,
            'category' => $categoryId,
            'location' => $this->store?->addresses->first()?->city,
            'time_left' => '1d', // Mocked or calculated if product has expiration
            'expires_at' => null, // Recommended future
            'distance_km' => $distance ? round((float) $distance, 2) : null,
        ];
    }

    private function calculateDiscountPercent(float $price, float $compareAt): int
    {
        if ($compareAt <= 0 || $compareAt <= $price) {
            return 0;
        }

        return (int) round((($compareAt - $price) / $compareAt) * 100);
    }
}
