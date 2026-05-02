<?php

namespace App\Application\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class StoreCategoryResource extends JsonResource
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
            'icon_path' => $this->icon_url,
            'icon_url' => $this->icon_url ? Storage::disk('public')->url($this->icon_url) : null,
            'image_category_path' => $this->image_category,
            'image_category_url' => $this->image_category ? Storage::disk('public')->url($this->image_category) : null,
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
        ];
    }
}
