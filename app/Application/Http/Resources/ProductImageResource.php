<?php

namespace App\Application\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductImageResource extends JsonResource
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
            'product_id' => $this->product_id,
            'path' => $this->image_url,
            'url' => $this->resolveImageUrl(),
            'sort_order' => $this->sort_order,
            'is_primary' => $this->is_primary,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }

    private function resolveImageUrl(): ?string
    {
        if (! $this->image_url) {
            return null;
        }

        if (Str::startsWith($this->image_url, ['http://', 'https://'])) {
            return $this->image_url;
        }

        return Storage::disk('public')->url($this->image_url);
    }
}
