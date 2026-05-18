<?php

namespace App\Domain\Product\Actions;

use App\Domain\Product\DTOs\ProductData;
use App\Domain\Product\Enums\ProductApprovalStatus;
use App\Domain\Product\Models\Product;
use App\Domain\Product\Models\ProductVariant;
use App\Domain\Product\Repositories\ProductRepository;
use App\Domain\Product\Support\PrepareProductIdentifiers;
use App\Domain\Product\Support\ProductDirectUpdateFields;
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
        $split = $this->splitApprovedProductData($product, $data);
        /** @var ProductData $directData */
        $directData = $split['directData'];
        /** @var ProductData $reviewData */
        $reviewData = $split['reviewData'];
        $reviewFields = $split['reviewFields'];

        if ($directData->touchedFields() !== []) {
            $product = $this->applyApprovedDirectUpdate($product, $directData, $storedPaths, $deletedPaths);
        }

        if ($reviewFields !== []) {
            $this->revisions->execute(
                $product->fresh(),
                $reviewData,
                $submittedBy,
                $reviewFields
            );
        }

        return $this->products->loadSellerProduct($product->fresh());
    }

    private function splitApprovedProductData(Product $product, ProductData $data): array
    {
        $product->loadMissing('categories:id', 'images', 'variants.attributes', 'offer.targets.variant');

        $directAttributes = [];
        $reviewAttributes = [];

        foreach ($data->attributes() as $field => $value) {
            if (ProductDirectUpdateFields::isTopLevelDirect($field)) {
                $directAttributes[$field] = $value;

                continue;
            }

            if (ProductReviewFields::requiresReview($field)) {
                $reviewAttributes[$field] = $value;

                continue;
            }
        }

        $directData = new ProductData(
            attributes: $directAttributes,
            categoryIds: [],
            images: [],
            variants: [],
            offer: [],
            hasCategoryIds: false,
            hasImages: false,
            hasVariants: false,
            hasOffer: false,
        );

        $reviewData = new ProductData(
            attributes: $reviewAttributes,
            categoryIds: [],
            images: [],
            variants: [],
            offer: [],
            hasCategoryIds: false,
            hasImages: false,
            hasVariants: false,
            hasOffer: false,
        );

        $reviewFields = array_values(array_filter(array_keys($reviewAttributes), fn (string $field) => ProductReviewFields::requiresReview($field)));

        if ($data->hasCategoryIds()) {
            $currentCategoryIds = $product->categories->pluck('id')->values()->all();

            if ($data->categoryIds() !== $currentCategoryIds) {
                $reviewData = $reviewData->withCategoryIds($data->categoryIds());
                $reviewFields[] = 'category_ids';
            }
        }

        if ($data->hasOffer()) {
            if (! $this->offerDataEquals($data->offer(), $this->currentOfferData($product))) {
                $reviewData = $reviewData->withOffer($data->offer());
                $reviewFields[] = 'offer';
            }
        }

        if ($data->hasVariants()) {
            [$directVariants, $requiresVariantReview, $hasDirectVariantChanges] = $this->splitApprovedVariants($product, $data->variants());

            if ($hasDirectVariantChanges) {
                $directData = $directData->withVariants($directVariants);
            }

            if ($requiresVariantReview) {
                $reviewData = $reviewData->withVariants($data->variants());
                $reviewFields[] = 'variants';
            }
        }

        if ($data->hasImages()) {
            [$requiresImageReview, $hasDirectImageChanges] = $this->inspectApprovedImages($product, $data->images());

            if ($hasDirectImageChanges) {
                $directData = $directData->withImages($data->images());
            }

            if ($requiresImageReview) {
                $reviewData = $reviewData->withImages($data->images());
                $reviewFields[] = 'images';
            }
        }

        return [
            'directData' => $directData,
            'reviewData' => $reviewData,
            'reviewFields' => array_values(array_unique($reviewFields)),
        ];
    }

    private function splitApprovedVariants(Product $product, array $variants): array
    {
        $currentVariants = $product->variants
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

        $requiresReview = count($variants) !== count($currentVariants);
        $hasDirectChanges = false;
        $liveVariants = [];

        foreach ($currentVariants as $index => $currentVariant) {
            $requestedVariant = $variants[$index] ?? $currentVariant;
            $liveVariant = $currentVariant;

            foreach (ProductDirectUpdateFields::VARIANT as $field) {
                if (array_key_exists($field, $requestedVariant)) {
                    if (($requestedVariant[$field] ?? null) !== ($currentVariant[$field] ?? null)) {
                        $hasDirectChanges = true;
                    }

                    $liveVariant[$field] = $requestedVariant[$field];
                }
            }

            $liveVariants[] = $liveVariant;
        }

        foreach ($variants as $index => $requestedVariant) {
            $currentVariant = $currentVariants[$index] ?? null;

            if (! $currentVariant) {
                $requiresReview = true;

                continue;
            }

            if ($this->variantReviewDataDiffers($requestedVariant, $currentVariant)) {
                $requiresReview = true;
            }
        }

        if (! $requiresReview) {
            return [$variants, false, $hasDirectChanges];
        }

        return [$liveVariants, true, $hasDirectChanges];
    }

    private function inspectApprovedImages(Product $product, array $images): array
    {
        $product->loadMissing('images');

        if (count($images) !== $product->images->count()) {
            return [true, true];
        }

        $imagesById = $product->images->keyBy('id');
        $imagesByPath = $product->images->keyBy('image_url');
        $hasDirectChanges = false;
        $requiresReview = false;

        foreach ($images as $index => $imageData) {
            $target = null;

            if (filled($imageData['id'] ?? null)) {
                $target = $imagesById->get((int) $imageData['id']);
            }

            if (! $target && filled($imageData['image_url'] ?? null)) {
                $target = $imagesByPath->get($imageData['image_url']);
            }

            if (! $target) {
                $target = $product->images->get($index);
            }

            if (! $target) {
                $requiresReview = true;
                $hasDirectChanges = true;

                continue;
            }

            if (($imageData['file'] ?? null) !== null) {
                $requiresReview = true;
            }

            if (array_key_exists('sort_order', $imageData) && (int) $imageData['sort_order'] !== (int) $target->sort_order) {
                $hasDirectChanges = true;
            }

            if (array_key_exists('is_primary', $imageData) && (bool) $imageData['is_primary'] !== (bool) $target->is_primary) {
                $hasDirectChanges = true;
            }
        }

        return [$requiresReview, $hasDirectChanges];
    }

    private function applyApprovedDirectUpdate(
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
        } elseif ($data->hasVariants()) {
            $product = $this->products->syncDerivedProductPricing($product, $pricingSummary);
        }

        if ($data->hasImages()) {
            $this->products->updateImageMetadata($product, $data->images());
        }

        if ($data->hasVariants()) {
            $this->products->replaceVariants($product, $resolvedVariants);
        }

        return $product->fresh();
    }

    private function variantReviewDataDiffers(array $requestedVariant, array $currentVariant): bool
    {
        foreach (['option_summary', 'sku', 'original_price', 'currency'] as $field) {
            if ($field === 'original_price') {
                if (! $this->sameNumericValue($requestedVariant[$field] ?? null, $currentVariant[$field] ?? null)) {
                    return true;
                }

                continue;
            }

            if (($requestedVariant[$field] ?? null) !== ($currentVariant[$field] ?? null)) {
                return true;
            }
        }

        return ($requestedVariant['attributes'] ?? []) !== ($currentVariant['attributes'] ?? []);
    }

    private function currentOfferData(Product $product): array
    {
        return [
            'type' => $product->offer?->type?->value ?? $product->offer?->type,
            'status' => $product->offer?->status?->value ?? $product->offer?->status,
            'label' => $product->offer?->label,
            'starts_at' => $product->offer?->starts_at?->toIso8601String(),
            'ends_at' => $product->offer?->ends_at?->toIso8601String(),
            'claim_expiration_minutes' => $product->offer?->claim_expiration_minutes,
            'fixed_amount' => $this->normalizeOptionalNumber($product->offer?->fixed_amount),
            'percentage_value' => $this->normalizeOptionalNumber($product->offer?->percentage_value),
            'max_discount' => $this->normalizeOptionalNumber($product->offer?->max_discount),
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
    }

    private function offerDataEquals(array $incomingOffer, array $currentOffer): bool
    {
        foreach ([
            'type',
            'status',
            'label',
            'starts_at',
            'ends_at',
            'claim_expiration_minutes',
            'buy_qty',
            'get_qty',
            'allow_mix_buy_variants',
            'allow_mix_reward_variants',
            'buy_variant_skus',
            'reward_variant_skus',
        ] as $field) {
            if (($incomingOffer[$field] ?? null) !== ($currentOffer[$field] ?? null)) {
                return false;
            }
        }

        foreach (['fixed_amount', 'percentage_value', 'max_discount'] as $field) {
            if (! $this->sameNumericValue($incomingOffer[$field] ?? null, $currentOffer[$field] ?? null)) {
                return false;
            }
        }

        return true;
    }

    private function sameNumericValue(mixed $left, mixed $right): bool
    {
        if ($left === null || $right === null) {
            return $left === $right;
        }

        return (float) $left === (float) $right;
    }

    private function normalizeOptionalNumber(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        return (float) $value;
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
