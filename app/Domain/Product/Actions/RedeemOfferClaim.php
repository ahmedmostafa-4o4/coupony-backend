<?php

namespace App\Domain\Product\Actions;

use App\Domain\Analytics\Services\AnalyticsCache;
use App\Domain\Points\Models\StorePointTransaction;
use App\Domain\Points\Models\UserPointTransaction;
use App\Domain\Points\Services\PointsService;
use App\Domain\Product\Enums\InventoryMode;
use App\Domain\Product\Enums\OfferClaimStatus;
use App\Domain\Product\Enums\ProductOfferType;
use App\Domain\Product\Events\OfferClaimRedeemed;
use App\Domain\Product\Models\OfferClaim;
use App\Domain\Product\Models\Product;
use App\Domain\Product\Models\ProductVariant;
use App\Domain\Store\Models\Store;
use App\Domain\User\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RedeemOfferClaim
{
    public function __construct(private readonly PointsService $points) {}

    public function execute(Store $store, string $qrCodeToken, User $redeemedBy, array $revenue = []): OfferClaim
    {
        $redemptionOutcome = DB::transaction(function () use ($store, $qrCodeToken, $redeemedBy, $revenue) {
            /** @var OfferClaim|null $claim */
            $claim = OfferClaim::query()
                ->where('qr_code_token', $qrCodeToken)
                ->lockForUpdate()
                ->first();

            if (! $claim || $claim->store_id !== $store->id) {
                throw new \DomainException(__('api.offer_claim.not_found_for_store'));
            }

            if ($claim->status === OfferClaimStatus::REDEEMED) {
                throw new \DomainException(__('api.offer_claim.already_redeemed'));
            }

            if ($claim->status !== OfferClaimStatus::ACTIVE) {
                throw new \DomainException(__('api.offer_claim.not_redeemable'));
            }

            if ($claim->isExpired()) {
                $claim->update(['status' => OfferClaimStatus::EXPIRED]);

                return ['expired' => true];
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

                if (! $variant || $variant->product_id !== $claim->product_id || ! $variant->is_active) {
                    throw new \DomainException(__('api.offer_claim.variants_not_redeemable'));
                }

                if (
                    $variant->inventory_mode === InventoryMode::TRACKED
                    && (int) ($variant->stock_qty ?? 0) < $quantity
                ) {
                    throw new \DomainException(__('api.offer_claim.insufficient_stock'));
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

            /** @var Product $product */
            $product = Product::query()
                ->whereKey($claim->product_id)
                ->lockForUpdate()
                ->firstOrFail();

            Product::query()
                ->whereKey($product->id)
                ->update([
                    'redemption_count' => DB::raw('redemption_count + 1'),
                ]);

            $resolvedRevenue = $this->resolveRevenue($claim, $product, $revenue);

            $claim->update([
                'status' => OfferClaimStatus::REDEEMED,
                'redeemed_at' => now(),
                'redeemed_by' => $redeemedBy->id,
                'revenue_amount' => $resolvedRevenue['amount'],
                'revenue_currency' => $resolvedRevenue['currency'],
            ]);

            $claim->loadMissing('user');

            $meta = [
                'claim_id' => $claim->id,
                'product_id' => $claim->product_id,
                'store_id' => $store->id,
            ];
            $userPoints = (int) config('points.offer_redeemed_user', 20);
            $storePoints = (int) config('points.offer_redeemed_store', 10);
            $pointsAwarded = false;

            if (! $this->hasRedeemPointsAward($claim)) {
                $this->points->addUserPoints(
                    $claim->user,
                    $userPoints,
                    'offer_redeemed',
                    null,
                    $store,
                    $claim,
                    null,
                    $meta
                );

                $this->points->addStorePoints(
                    $store,
                    $storePoints,
                    'offer_redeemed',
                    null,
                    $claim->user,
                    $claim,
                    null,
                    $meta
                );

                $pointsAwarded = true;
            }

            return [
                'expired' => false,
                'claim' => $claim->fresh(),
                'user_points_awarded' => $pointsAwarded,
                'store_points_awarded' => $pointsAwarded,
                'user_points' => $userPoints,
                'store_points' => $storePoints,
            ];
        });

        if ($redemptionOutcome['expired']) {
            throw new \DomainException(__('api.offer_claim.expired'));
        }

        OfferClaimRedeemed::dispatch(
            $redemptionOutcome['claim'],
            $store,
            $redeemedBy,
            $redemptionOutcome['user_points_awarded'],
            $redemptionOutcome['store_points_awarded'],
            $redemptionOutcome['user_points'],
            $redemptionOutcome['store_points'],
        );

        AnalyticsCache::invalidateSeller($store->id);

        return $redemptionOutcome['claim'];
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

    private function resolveRevenue(OfferClaim $claim, Product $product, array $manualRevenue): array
    {
        if (array_key_exists('amount', $manualRevenue) && array_key_exists('currency', $manualRevenue)) {
            return [
                'amount' => $manualRevenue['amount'],
                'currency' => $manualRevenue['currency'],
            ];
        }

        $snapshot = $claim->offer_snapshot ?? [];
        $offerType = data_get($snapshot, 'offer.type');
        $revenueVariants = $offerType === ProductOfferType::BUY_X_GET_Y->value
            ? collect($snapshot['selected_buy_variants'] ?? [])
            : collect($snapshot['selected_variants'] ?? []);

        $amount = $revenueVariants
            ->filter(fn (array $variant) => is_numeric($variant['price'] ?? null))
            ->sum(fn (array $variant) => (float) $variant['price']);

        if ($revenueVariants->isEmpty()) {
            $amount = (float) $product->base_price;
        }

        return [
            'amount' => number_format($amount, 2, '.', ''),
            'currency' => $revenueVariants
                ->pluck('currency')
                ->filter()
                ->first() ?? $product->currency,
        ];
    }
}
