<?php

namespace App\Application\Http\Resources;

use App\Domain\Explore\Support\DiscountCalculator;
use App\Domain\Product\Models\ProductOffer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FollowingFeedItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $offer = new ProductOffer([
            'type' => $this->offer_type,
            'percentage_value' => $this->percentage_value,
            'fixed_amount' => $this->fixed_amount,
        ]);

        $basePrice = (float) $this->compare_at_price;
        [$savePercent, $discountedPrice] = DiscountCalculator::calculate($offer, $basePrice);

        $primaryImage = $this->images->firstWhere('is_primary', true) ?? $this->images->first();
        $category = $this->categories->first();

        // Since we follow locale for category_ar according to user instruction,
        // we'll fetch the name using the locale-aware translation method if available,
        // or just fallback to the appropriate field.
        $categoryLabel = $category ? $category->name : null;

        return [
            'source_type' => $this->source_type,
            'recommendation_reason' => $this->recommendation_reason,
            'store' => [
                'id' => $this->store_id,
                'name' => $this->store_name,
                'image_url' => $this->store_image_url,
                'is_followed' => (bool) $this->store_is_followed,
            ],
            'offer' => [
                'id' => $this->offer_id,
                'image_url' => $primaryImage ? $primaryImage->image_url : null,
                'title' => $this->title, // Product name
                'original_price' => $basePrice,
                'discounted_price' => $discountedPrice,
                'save_percent' => $savePercent,
                'category' => $category ? $category->slug : null,
                'category_ar' => $categoryLabel,
                'store_name' => $this->store_name, // Requested by spec
                'is_liked' => (bool) $this->is_liked,
                'likes_count' => (int) $this->likes_count,
                'comments_count' => (int) $this->comments_count,
                'is_saved' => (bool) $this->is_saved,
                'created_at' => $this->created_at?->toIso8601String(),
            ],
        ];
    }
}
