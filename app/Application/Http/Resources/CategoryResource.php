<?php

namespace App\Application\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class CategoryResource extends JsonResource
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
            'name_ar' => $this->name_ar,
            'name_en' => $this->name_en,
            'slug' => $this->slug,
            'description' => $this->description,
            'icon_path' => $this->icon_url,
            'icon_url' => $this->icon_url ? Storage::disk('public')->url($this->icon_url) : null,
            'parent_id' => $this->parent_id,
            'parent' => $this->whenLoaded('parent', function () {
                return $this->parent ? [
                    'id' => $this->parent->id,
                    'name' => $this->parent->name,
                    'name_ar' => $this->parent->name_ar,
                    'name_en' => $this->parent->name_en,
                    'slug' => $this->parent->slug,
                ] : null;
            }),
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
            'products_count' => $this->whenCounted('products'),
        ];
    }
}
