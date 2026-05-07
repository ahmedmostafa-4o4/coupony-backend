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
            'rating_avg' => (float) ($this->rating_avg ?? 0),
            'rating_count' => (int) ($this->rating_count ?? 0),
            'comments_count' => (int) ($this->comments_count ?? 0),
            'likes_count' => (int) ($this->likes_count ?? 0),
            'views_count' => (int) ($this->views_count ?? 0),
            'unique_viewers_count' => (int) ($this->unique_viewers_count ?? 0),
            'is_liked' => (bool) ($this->is_liked ?? false),
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
            'requested_changes' => $this->whenLoaded('latestRequestedChangesRevision', function () {
                return $this->latestRequestedChangesRevision
                    ? ($this->latestRequestedChangesRevision->requested_changes ?? [])
                    : [];
            }),
            'pending_revision' => $this->whenLoaded('pendingRevision', function () {
                return $this->pendingRevision
                    ? ProductRevisionResource::make($this->pendingRevision)->resolve()
                    : null;
            }),
            'recent_viewers' => $this->when(
                $this->resource->offsetExists('recent_viewers'),
                fn() => $this->recent_viewers ?? []
            ),
        ];
    }
}
