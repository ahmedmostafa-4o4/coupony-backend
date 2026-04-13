<?php

namespace App\Domain\Product\Actions;

use App\Domain\Product\Models\Product;
use App\Domain\Product\Models\ProductVariant;
use App\Domain\Product\Repositories\ProductRepository;
use Illuminate\Support\Facades\DB;

class CreateProductVariant
{
    public function __construct(
        private readonly ProductRepository $products,
        private readonly ResolveVariantOfferPricing $pricing,
    )
    {
    }

    public function execute(Product $product, array $attributes): ProductVariant
    {
        return DB::transaction(function () use ($product, $attributes) {
            $product->loadMissing('offer');
            $resolvedAttributes = $this->pricing->resolve([$attributes], $this->currentOffer($product))[0];
            $variant = $this->products->createVariant($product, $resolvedAttributes);
            $this->syncProductPricingSummary($product);

            return $variant;
        });
    }

    private function syncProductPricingSummary(Product $product): void
    {
        $variants = $product->fresh()->variants()->orderBy('sort_order')->orderBy('id')->get()
            ->map(fn(ProductVariant $variant) => [
                'is_default' => $variant->is_default,
                'sort_order' => $variant->sort_order,
                'sku' => $variant->sku,
                'price' => $variant->price,
                'compare_at_price' => $variant->compare_at_price,
            ])
            ->all();

        $this->products->syncDerivedProductPricing($product, $this->pricing->deriveProductPricingSummary($variants));
    }

    private function currentOffer(Product $product): array
    {
        return [
            'type' => $product->offer?->type?->value ?? $product->offer?->type,
            'fixed_amount' => $product->offer?->fixed_amount,
            'percentage_value' => $product->offer?->percentage_value,
        ];
    }
}
