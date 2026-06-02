<?php

namespace App\Application\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BannerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'image_url' => $this->resolveImageUrl($this->image_url),
            'image_path' => $this->image_url,
            'discount_label' => $this->discount_label,
            'date_range' => $this->date_range,
            'cta_label' => $this->cta_label,
            'terms_of_use' => $this->terms_of_use,
            'end_time' => $this->end_time?->toIso8601String(),
            'priority' => $this->priority,
            'is_active' => $this->is_active,
            'status' => $this->status?->value ?? $this->status,
            'likes_count' => (int) ($this->likes_count ?? 0),
            'is_liked' => (bool) ($this->is_liked ?? false),
            'is_favorited' => (bool) ($this->is_favorited ?? false),
            'approved_at' => $this->approved_at?->toIso8601String(),
            'approved_by' => $this->approved_by,
            'requested_by' => $this->requested_by,
            'rejection_reason' => $this->rejection_reason,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'store' => PublicStoreResource::make($this->whenLoaded('store')),
            'branches' => StoreAddressResource::collection($this->whenLoaded('branches')),
            'offers' => BannerOfferResource::collection($this->whenLoaded('offers')),
            'requested_by_user' => UserResource::make($this->whenLoaded('requestedBy')),
            'approved_by_user' => UserResource::make($this->whenLoaded('approvedBy')),
        ];
    }

    private function resolveImageUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        return Storage::disk('public')->url($path);
    }
}
