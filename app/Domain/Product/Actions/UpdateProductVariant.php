<?php

namespace App\Domain\Product\Actions;

use App\Domain\Product\Models\Product;
use App\Domain\Product\Models\ProductVariant;
use App\Domain\Product\Repositories\ProductRepository;
use Illuminate\Support\Facades\DB;

class UpdateProductVariant
{
    public function __construct(
        private readonly ProductRepository $products,
        private readonly ResolveVariantOfferPricing $pricing,
    )
    {
    }

    public function execute(Product $product, ProductVariant $variant, array $attributes): ProductVariant
    {
        return DB::transaction(function () use ($product, $variant, $attributes) {
            $product->loadMissing('offer');
            $resolvedAttributes = $this->pricing->resolve([[
                'title' => $attributes['title'] ?? $variant->title,
                'option_summary' => $attributes['option_summary'] ?? $variant->option_summary,
                'sku' => $attributes['sku'] ?? $variant->sku,
                'barcode' => $attributes['barcode'] ?? $variant->barcode,
                'original_price' => $attributes['original_price'] ?? (float) $variant->original_price,
                'currency' => $attributes['currency'] ?? $variant->currency,
                'sort_order' => $attributes['sort_order'] ?? $variant->sort_order,
                'is_default' => $attributes['is_default'] ?? $variant->is_default,
                'is_active' => $attributes['is_active'] ?? $variant->is_active,
                'inventory_mode' => $attributes['inventory_mode'] ?? ($variant->inventory_mode?->value ?? $variant->inventory_mode),
                'stock_qty' => array_key_exists('stock_qty', $attributes) ? $attributes['stock_qty'] : $variant->stock_qty,
                'low_stock_threshold' => array_key_exists('low_stock_threshold', $attributes) ? $attributes['low_stock_threshold'] : $variant->low_stock_threshold,
                'allow_backorder' => $attributes['allow_backorder'] ?? $variant->allow_backorder,
            ]], $this->currentOffer($product))[0];

            $updatedVariant = $this->products->updateVariant($product, $variant, $resolvedAttributes);
            $this->syncProductPricingSummary($product);

            return $updatedVariant;
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
