<?php

namespace App\Domain\Product\Actions;

use App\Domain\Product\Enums\InventoryMode;
use App\Domain\Product\Enums\OfferClaimStatus;
use App\Domain\Product\Models\OfferClaim;
use App\Domain\Product\Models\Product;
use App\Domain\Product\Models\ProductVariant;
use App\Domain\Store\Models\Store;
use App\Domain\User\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RedeemOfferClaim
{
    public function execute(Store $store, string $qrCodeToken, User $redeemedBy): OfferClaim
    {
        return DB::transaction(function () use ($store, $qrCodeToken, $redeemedBy) {
            /** @var OfferClaim|null $claim */
            $claim = OfferClaim::query()
                ->where('qr_code_token', $qrCodeToken)
                ->lockForUpdate()
                ->first();

            if (!$claim || $claim->store_id !== $store->id) {
                throw new \DomainException('The scanned claim could not be found for this store.');
            }

            if ($claim->status === OfferClaimStatus::REDEEMED) {
                throw new \DomainException('This claim has already been redeemed.');
            }

            if ($claim->status !== OfferClaimStatus::ACTIVE) {
                throw new \DomainException('This claim is not redeemable.');
            }

            if ($claim->isExpired()) {
                $claim->update(['status' => OfferClaimStatus::EXPIRED]);

                throw new \DomainException('This claim has expired.');
            }

            $snapshot = $claim->offer_snapshot ?? [];
            $variantIds = collect([
                ...collect($snapshot['selected_variants'] ?? [])->pluck('id')->all(),
                ...collect($snapshot['selected_buy_variants'] ?? [])->pluck('id')->all(),
                ...collect($snapshot['selected_reward_variants'] ?? [])->pluck('id')->all(),
            ]);

            $usageCounts = $variantIds->countBy();

            /** @var Collection<string, ProductVariant> $variants */
            $variants = ProductVariant::query()
                ->whereIn('id', $usageCounts->keys()->all())
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($usageCounts as $variantId => $quantity) {
                /** @var ProductVariant|null $variant */
                $variant = $variants->get($variantId);

                if (!$variant || $variant->product_id !== $claim->product_id || !$variant->is_active) {
                    throw new \DomainException('One or more claimed variants are no longer redeemable.');
                }

                if (
                    $variant->inventory_mode === InventoryMode::TRACKED
                    && (int) ($variant->stock_qty ?? 0) < $quantity
                ) {
                    throw new \DomainException('Insufficient stock is available to redeem this claim.');
                }
            }

            foreach ($usageCounts as $variantId => $quantity) {
                /** @var ProductVariant $variant */
                $variant = $variants->get($variantId);
                $updates = [
                    'redemption_count' => DB::raw("redemption_count + {$quantity}"),
                ];

                if ($variant->inventory_mode === InventoryMode::TRACKED) {
                    $updates['stock_qty'] = max(0, (int) $variant->stock_qty - $quantity);
                }

                ProductVariant::query()->whereKey($variantId)->update($updates);
            }

            Product::query()
                ->whereKey($claim->product_id)
                ->lockForUpdate()
                ->update([
                    'redemption_count' => DB::raw('redemption_count + 1'),
                ]);

            $claim->update([
                'status' => OfferClaimStatus::REDEEMED,
                'redeemed_at' => now(),
                'redeemed_by' => $redeemedBy->id,
            ]);

            return $claim->fresh();
        });
    }
}
