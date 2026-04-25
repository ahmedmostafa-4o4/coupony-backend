<?php

namespace App\Domain\Product\Actions;

use App\Domain\Product\DTOs\ProductData;
use App\Domain\Product\Enums\ProductApprovalStatus;
use App\Domain\Product\Models\Product;
use App\Domain\Product\Models\ProductVariant;
use App\Domain\Product\Repositories\ProductRepository;
use App\Domain\Product\Support\PrepareProductIdentifiers;
use App\Domain\Product\Support\ProductReviewFields;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\DB;

class UpdateProduct
{
    public function __construct(
        private readonly ProductRepository $products,
        private readonly CreateOrUpdatePendingProductRevision $revisions,
        private readonly ResolveVariantOfferPricing $pricing,
        private readonly PrepareProductIdentifiers $identifiers,
    ) {}

    public function execute(Product $product, ProductData $data, User $submittedBy): Product
    {
        $storedPaths = [];
        $deletedPaths = [];

        try {
            return DB::transaction(function () use ($product, $data, $submittedBy, &$storedPaths, &$deletedPaths) {
                $data = $this->identifiers->forUpdate($product, $data);

                if ($product->approval_status === ProductApprovalStatus::APPROVED) {
                    return $this->updateApprovedProduct($product, $data, $submittedBy, $storedPaths, $deletedPaths);
                }

                $product = $this->applyLiveUpdate($product, $data, $storedPaths, $deletedPaths);

                $this->revisions->execute($product, $data, $submittedBy);

                return $this->products->loadSellerProduct($product);
            });
        } catch (\Throwable $throwable) {
            $this->products->deleteFiles($storedPaths);

            throw $throwable;
        }
    }

    private function updateApprovedProduct(
        Product $product,
        ProductData $data,
        User $submittedBy,
        array &$storedPaths,
        array &$deletedPaths,
    ): Product {
        $touchedFields = $data->touchedFields();

        $reviewFields = collect($touchedFields)
            ->filter(fn (string $field) => ProductReviewFields::requiresReview($field))
            ->values()
            ->all();

        $directFields = collect($touchedFields)
            ->reject(fn (string $field) => ProductReviewFields::requiresReview($field))
            ->values()
            ->all();

        if ($directFields !== []) {
            $product = $this->applyLiveUpdate($product, $data->onlyFields($directFields), $storedPaths, $deletedPaths);
        }

        if ($reviewFields !== []) {
            $this->revisions->execute(
                $product->fresh(),
                $data->onlyFields($reviewFields),
                $submittedBy,
                $reviewFields
            );
        }

        return $this->products->loadSellerProduct($product->fresh());
    }

    private function applyLiveUpdate(
        Product $product,
        ProductData $data,
        array &$storedPaths,
        array &$deletedPaths,
    ): Product {
        [$resolvedVariants, $pricingSummary] = $this->resolvePricingState($product, $data);

        if ($data->attributes() !== []) {
            $product = $this->products->update($product, [
                ...$data->attributes(),
                ...$pricingSummary,
            ]);
        } elseif ($data->hasVariants() || $data->hasOffer()) {
            $product = $this->products->syncDerivedProductPricing($product, $pricingSummary);
        }

        if ($data->hasCategoryIds()) {
            $this->products->syncCategories($product, $data->categoryIds());
        }

        if ($data->hasImages()) {
            $imageResult = $this->products->replaceImages($product, $data->images());
            $storedPaths = [...$storedPaths, ...$imageResult['stored']];
            $deletedPaths = [...$deletedPaths, ...$imageResult['deleted']];

            DB::afterCommit(function () use ($imageResult) {
                $this->products->deleteFiles($imageResult['deleted']);
            });
        }

        if ($data->hasVariants() || $data->hasOffer()) {
            $this->products->replaceVariants($product, $resolvedVariants);
        }

        if ($data->hasOffer()) {
            $this->products->syncOffer($product, $data->offer());
        }

        return $product->fresh();
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
                'starts_at' => $product->offer?->starts_at?->toIso8601String(),
                'ends_at' => $product->offer?->ends_at?->toIso8601String(),
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
                        ->where('role', \App\Domain\Product\Enums\ProductOfferTargetRole::BUY)
                        ->pluck('variant.sku')
                        ->filter()
                        ->values()
                        ->all()
                    : [],
                'reward_variant_skus' => $product->offer?->targets
                    ? $product->offer->targets
                        ->where('role', \App\Domain\Product\Enums\ProductOfferTargetRole::REWARD)
                        ->pluck('variant.sku')
                        ->filter()
                        ->values()
                        ->all()
                    : [],
            ];

        $resolvedVariants = $this->pricing->resolve($variants, $offer);

        return [$resolvedVariants, $this->pricing->deriveProductPricingSummary($resolvedVariants)];
    }
}
