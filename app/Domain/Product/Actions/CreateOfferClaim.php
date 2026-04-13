<?php

namespace App\Domain\Product\Actions;

use App\Domain\Product\Enums\OfferClaimStatus;
use App\Domain\Product\Enums\ProductApprovalStatus;
use App\Domain\Product\Enums\ProductOfferStatus;
use App\Domain\Product\Enums\ProductOfferTargetRole;
use App\Domain\Product\Enums\ProductOfferType;
use App\Domain\Product\Enums\ProductStatus;
use App\Domain\Product\Models\OfferClaim;
use App\Domain\Product\Models\Product;
use App\Domain\Product\Models\ProductOffer;
use App\Domain\Product\Models\ProductOfferVariantTarget;
use App\Domain\Product\Models\ProductVariant;
use App\Domain\User\Models\User;
use Illuminate\Support\Str;
use Log;

class CreateOfferClaim
{
    public function execute(Product $product, User $user, array $input): OfferClaim
    {
        $product = $product->load([
            'store',
            'offer.targets.variant',
            'variants.attributes',
        ]);

        if (
            $product->status !== ProductStatus::ACTIVE
            || $product->approval_status !== ProductApprovalStatus::APPROVED
        ) {
            throw new \DomainException('Only approved active products can be claimed.');
        }

        /** @var ProductOffer|null $offer */
        $offer = $product->offer;

        if (!$offer) {
            throw new \DomainException('This product does not have an offer available for claiming.');
        }

        $now = now();

        if ($offer->status !== ProductOfferStatus::ACTIVE) {
            throw new \DomainException('This offer is not active.');
        }

        if ($offer->starts_at !== null && $offer->starts_at->isFuture()) {
            throw new \DomainException('This offer is not yet claimable.');
        }

        if ($offer->ends_at !== null && $offer->ends_at->lt($now)) {
            throw new \DomainException('This offer is no longer claimable.');
        }

        $snapshot = [
            'claimed_at' => $now->toIso8601String(),
            'product' => [
                'id' => $product->id,
                'store_id' => $product->store_id,
                'title' => $product->title,
                'slug' => $product->slug,
                'currency' => $product->currency,
            ],
            'offer' => [
                'id' => $offer->id,
                'type' => $offer->type?->value ?? $offer->type,
                'status' => $offer->status?->value ?? $offer->status,
                'label' => $offer->label,
                'starts_at' => $offer->starts_at?->toIso8601String(),
                'ends_at' => $offer->ends_at?->toIso8601String(),
                'claim_expiration_minutes' => $offer->claim_expiration_minutes,
                'fixed_amount' => $offer->fixed_amount,
                'percentage_value' => $offer->percentage_value,
                'max_discount' => $offer->max_discount,
                'buy_qty' => $offer->buy_qty,
                'get_qty' => $offer->get_qty,
                'allow_mix_buy_variants' => $offer->allow_mix_buy_variants,
                'allow_mix_reward_variants' => $offer->allow_mix_reward_variants,
            ],
        ];

        if ($offer->type === ProductOfferType::BUY_X_GET_Y) {
            $snapshot['selected_buy_variants'] = $this->resolveTargetedSelections(
                $offer,
                $product,
                $input['buy_variant_ids'] ?? [],
                ProductOfferTargetRole::BUY
            );
            $snapshot['selected_reward_variants'] = $this->resolveTargetedSelections(
                $offer,
                $product,
                $input['reward_variant_ids'] ?? [],
                ProductOfferTargetRole::REWARD
            );
        } else {
            $snapshot['selected_variants'] = $this->resolveStandardSelections($product, $input['variant_ids'] ?? []);
        }

        $expiresAt = $offer->claim_expiration_minutes !== null
            ? $now->copy()->addMinutes($offer->claim_expiration_minutes)
            : null;

        return OfferClaim::create([
            'user_id' => $user->id,
            'store_id' => $product->store_id,
            'product_id' => $product->id,
            'offer_id' => $offer->id,
            'status' => OfferClaimStatus::ACTIVE,
            'claim_token' => Str::random(64),
            'qr_code_token' => Str::random(64),
            'offer_snapshot' => $snapshot,
            'expires_at' => $expiresAt,
        ])->fresh();
    }

    private function resolveStandardSelections(Product $product, array $selectedVariantIds): array
    {
        $variants = $product->variants
            ->filter(fn(ProductVariant $variant) => $variant->is_active)
            ->keyBy('id');

        if ($variants->isEmpty()) {
            return [];
        }

        if ($selectedVariantIds === []) {
            throw new \DomainException('At least one active variant must be selected for this claim.');
        }
        Log::debug('Variants for claim: ' . $variants);
        Log::debug('Selected variant IDs for claim: ' . implode(', ', $selectedVariantIds));
        return collect($selectedVariantIds)
            ->map(function (string $variantId) use ($variants) {
                /** @var ProductVariant|null $variant */
                $variant = $variants->get($variantId);

                if (!$variant) {
                    throw new \DomainException('The selected claim variant is invalid.');
                }

                return $this->snapshotVariant($variant);
            })
            ->values()
            ->all();
    }

    private function resolveTargetedSelections(
        ProductOffer $offer,
        Product $product,
        array $selectedVariantIds,
        ProductOfferTargetRole $role
    ): array {
        $selectedIds = collect($selectedVariantIds)->values();
        $allowedTargets = $offer->targets
            ->filter(fn(ProductOfferVariantTarget $target) => ($target->role?->value ?? $target->role) === $role->value)
            ->filter(fn(ProductOfferVariantTarget $target) => $target->variant && $target->variant->is_active)
            ->keyBy('variant_id');

        if ($selectedIds->isEmpty()) {
            throw new \DomainException("The selected {$role->value} variants are required for this offer.");
        }

        $allowMix = $role === ProductOfferTargetRole::BUY
            ? $offer->allow_mix_buy_variants
            : $offer->allow_mix_reward_variants;

        if (!$allowMix && $selectedIds->unique()->count() > 1) {
            throw new \DomainException("This offer does not allow mixing {$role->value} variants.");
        }

        return $selectedIds
            ->map(function (string $variantId) use ($allowedTargets, $product, $role) {
                /** @var ProductOfferVariantTarget|null $target */
                $target = $allowedTargets->get($variantId);

                if (!$target || !$target->variant || $target->variant->product_id !== $product->id) {
                    throw new \DomainException("The selected {$role->value} variant is not allowed for this offer.");
                }

                return $this->snapshotVariant($target->variant);
            })
            ->values()
            ->all();
    }

    private function snapshotVariant(ProductVariant $variant): array
    {
        return [
            'id' => $variant->id,
            'sku' => $variant->sku,
            'title' => $variant->title,
            'price' => $variant->price,
            'currency' => $variant->currency,
            'inventory_mode' => $variant->inventory_mode?->value ?? $variant->inventory_mode,
        ];
    }
}
