<?php

namespace App\Application\Http\Resources;

use App\Domain\Product\Models\ProductImage;
use App\Domain\Product\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BannerOfferResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $product = $this->whenLoaded('product');

        return [
            'id' => $this->id,
            'type' => $this->type?->value ?? $this->type,
            'status' => $this->status?->value ?? $this->status,
            'label' => $this->label,
            'starts_at' => $this->starts_at?->toIso8601String(),
            'ends_at' => $this->ends_at?->toIso8601String(),
            'claim_expiration_minutes' => $this->claim_expiration_minutes,
            'fixed_amount' => $this->fixed_amount,
            'percentage_value' => $this->percentage_value,
            'max_discount' => $this->max_discount,
            'buy_qty' => $this->buy_qty,
            'get_qty' => $this->get_qty,
            'allow_mix_buy_variants' => $this->allow_mix_buy_variants,
            'allow_mix_reward_variants' => $this->allow_mix_reward_variants,
            'product' => $this->whenLoaded('product', function () use ($product) {
                $primaryImage = $product->images
                    ? ($product->images->firstWhere('is_primary', true) ?? $product->images->first())
                    : null;

                return [
                    'id' => $product->id,
                    'store_id' => $product->store_id,
                    'title' => $product->title,
                    'short_description' => $product->short_description,
                    'description' => $product->description,
                    'currency' => $product->currency,
                    'base_price' => $product->base_price,
                    'compare_at_price' => $product->compare_at_price,
                    'status' => $product->status?->value ?? $product->status,
                    'approval_status' => $product->approval_status?->value ?? $product->approval_status,
                    'primary_image_url' => $primaryImage instanceof ProductImage
                        ? $this->resolveImageUrl($primaryImage->image_url)
                        : null,
                    'variants' => $product->relationLoaded('variants')
                        ? $product->variants
                            ->map(fn (ProductVariant $variant) => ProductVariantResource::make($variant)->resolve())
                            ->values()
                            ->all()
                        : [],
                ];
            }),
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
