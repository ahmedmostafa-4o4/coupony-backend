<?php

namespace App\Domain\Product\Actions;

use App\Domain\Points\Models\StorePointTransaction;
use App\Domain\Points\Models\UserPointTransaction;
use App\Domain\Points\Services\PointsService;
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
    public function __construct(private readonly PointsService $points)
    {
    }

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

            $claim->loadMissing('user');

            $meta = [
                'claim_id' => $claim->id,
                'product_id' => $claim->product_id,
                'store_id' => $store->id,
            ];

            if (!$this->hasRedeemPointsAward($claim)) {
                $this->points->addUserPoints(
                    $claim->user,
                    (int) config('points.offer_redeemed_user', 20),
                    'offer_redeemed',
                    null,
                    $store,
                    $claim,
                    null,
                    $meta
                );

                $this->points->addStorePoints(
                    $store,
                    (int) config('points.offer_redeemed_store', 10),
                    'offer_redeemed',
                    null,
                    $claim->user,
                    $claim,
                    null,
                    $meta
                );
            }

            return $claim->fresh();
        });
    }

    private function hasRedeemPointsAward(OfferClaim $claim): bool
    {
        return UserPointTransaction::query()
            ->where('offer_claim_id', $claim->id)
            ->where('reason', 'offer_redeemed')
            ->exists()
            || StorePointTransaction::query()
                ->where('offer_claim_id', $claim->id)
                ->where('reason', 'offer_redeemed')
                ->exists();
    }
}