<?php

namespace App\Application\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductVariantResource extends JsonResource
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
            'title' => $this->title,
            'option_summary' => $this->option_summary,
            'sku' => $this->sku,
            'barcode' => $this->barcode,
            'price' => $this->price,
            'compare_at_price' => $this->compare_at_price,
            'currency' => $this->currency,
            'sort_order' => $this->sort_order,
            'is_default' => $this->is_default,
            'is_active' => $this->is_active,
            'attributes' => $this->whenLoaded('attributes', function () {
                return $this->attributes
                    ->map(fn($attribute) => [
                        'id' => $attribute->id,
                        'attribute_name' => $attribute->attribute_name,
                        'attribute_value' => $attribute->attribute_value,
                        'sort_order' => $attribute->sort_order,
                    ])
                    ->values();
            }, []),
        ];
    }
}
