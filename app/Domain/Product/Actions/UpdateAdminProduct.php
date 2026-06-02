<?php

namespace App\Domain\Product\Actions;

use App\Domain\Product\DTOs\ProductData;
use App\Domain\Product\Enums\ProductApprovalStatus;
use App\Domain\Product\Enums\ProductOfferTargetRole;
use App\Domain\Product\Enums\ProductStatus;
use App\Domain\Product\Models\Product;
use App\Domain\Product\Models\ProductVariant;
use App\Domain\Product\Repositories\ProductRepository;
use App\Domain\Product\Support\PrepareProductIdentifiers;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\DB;

class UpdateAdminProduct
{
    public function __construct(
        private readonly ProductRepository $products,
        private readonly ResolveVariantOfferPricing $pricing,
        private readonly PrepareProductIdentifiers $identifiers,
    ) {}

    public function execute(Product $product, ProductData $data, User $admin): Product
    {
        $storedPaths = [];
        $deletedPaths = [];

        try {
            return DB::transaction(function () use ($product, $data, $admin, &$storedPaths, &$deletedPaths) {
                $data = $this->identifiers->forUpdate($product, $data);
                [$resolvedVariants, $pricingSummary] = $this->resolvePricingState($product, $data);
                $stateAttributes = $this->liveStateAttributes($product, $admin);

                if ($data->attributes() !== []) {
                    $product = $this->products->update($product, [
                        ...$data->attributes(),
                        ...$pricingSummary,
                        ...$stateAttributes,
                    ]);
                } elseif ($data->hasVariants() || $data->hasOffer()) {
                    $product = $this->products->update($product, [
                        ...$pricingSummary,
                        ...$stateAttributes,
                    ]);
                } else {
                    $product = $this->products->update($product, $stateAttributes);
                }

                if ($data->hasCategoryIds()) {
                    $this->products->syncCategories($product, $data->categoryIds());
                }

                if ($data->hasImages()) {
                    $imageResult = $this->products->replaceImages($product, $data->images());
                    $storedPaths = $imageResult['stored'];
                    $deletedPaths = $imageResult['deleted'];

                    DB::afterCommit(function () use ($deletedPaths) {
                        $this->products->deleteFiles($deletedPaths);
                    });
                }

                if ($data->hasVariants() || $data->hasOffer()) {
                    $this->products->replaceVariants($product, $resolvedVariants);
                }

                if ($data->hasOffer()) {
                    $this->products->syncOffer($product, $data->offer());
                }

                return $this->products->loadAdminProduct($product->fresh());
            });
        } catch (\Throwable $throwable) {
            $this->products->deleteFiles($storedPaths);

            throw $throwable;
        }
    }

    private function resolvePricingState(Product $product, ProductData $data): array
    {
        if (! $data->hasVariants() && ! $data->hasOffer()) {
            return [[], []];
        }

        $product->loadMissing('offer.targets.variant', 'variants.attributes');

        $variants = $data->hasVariants()
            ? $data->variants()
            : $product->variants
                ->map(fn (ProductVariant $variant) => [
                    'title' => $variant->title,
                    'option_summary' => $variant->option_summary,
                    'sku' => $variant->sku,
                    'barcode' => $variant->barcode,
                    'original_price' => (float) $variant->original_price,
                    'currency' => $variant->currency,
                    'sort_order' => $variant->sort_order,
                    'is_default' => $variant->is_default,
                    'is_active' => $variant->is_active,
                    'inventory_mode' => $variant->inventory_mode?->value ?? $variant->inventory_mode,
                    'stock_qty' => $variant->stock_qty,
                    'low_stock_threshold' => $variant->low_stock_threshold,
                    'allow_backorder' => $variant->allow_backorder,
                    'attributes' => $variant->attributes
                        ->map(fn ($attribute) => [
                            'attribute_name' => $attribute->attribute_name,
                            'attribute_value' => $attribute->attribute_value,
                            'sort_order' => $attribute->sort_order,
                        ])
                        ->values()
                        ->all(),
                ])
                ->values()
                ->all();

        $offer = $data->hasOffer()
            ? $data->offer()
            : [
                'type' => $product->offer?->type?->value ?? $product->offer?->type,
                'status' => $product->offer?->status?->value ?? $product->offer?->status,
                'label' => $product->offer?->label,
                'duration_days' => $product->offer?->duration_days,
                'duration_hours' => $product->offer?->duration_hours,
                'claim_expiration_minutes' => $product->offer?->claim_expiration_minutes,
                'fixed_amount' => $product->offer?->fixed_amount,
                'percentage_value' => $product->offer?->percentage_value,
                'max_discount' => $product->offer?->max_discount,
                'buy_qty' => $product->offer?->buy_qty,
                'get_qty' => $product->offer?->get_qty,
                'allow_mix_buy_variants' => $product->offer?->allow_mix_buy_variants,
                'allow_mix_reward_variants' => $product->offer?->allow_mix_reward_variants,
                'buy_variant_skus' => $product->offer?->targets
                    ? $product->offer->targets
                        ->where('role', ProductOfferTargetRole::BUY)
                        ->pluck('variant.sku')
                        ->filter()
                        ->values()
                        ->all()
                    : [],
                'reward_variant_skus' => $product->offer?->targets
                    ? $product->offer->targets
                        ->where('role', ProductOfferTargetRole::REWARD)
                        ->pluck('variant.sku')
                        ->filter()
                        ->values()
                        ->all()
                    : [],
            ];

        $resolvedVariants = $this->pricing->resolve($variants, $offer);

        return [$resolvedVariants, $this->pricing->deriveProductPricingSummary($resolvedVariants)];
    }

    private function liveStateAttributes(Product $product, User $admin): array
    {
        return [
            'status' => ProductStatus::ACTIVE,
            'approval_status' => ProductApprovalStatus::APPROVED,
            'published_revision_no' => max(1, (int) $product->published_revision_no),
            'approved_at' => $product->approval_status === ProductApprovalStatus::APPROVED && $product->approved_at
                ? $product->approved_at
                : now(),
            'approved_by' => $product->approval_status === ProductApprovalStatus::APPROVED && $product->approved_by
                ? $product->approved_by
                : $admin->id,
            'rejected_at' => null,
            'rejected_by' => null,
            'rejection_reason' => null,
        ];
    }
}
