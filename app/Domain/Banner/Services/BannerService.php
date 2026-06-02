<?php

namespace App\Domain\Banner\Services;

use App\Domain\Banner\Enums\BannerClaimStatus;
use App\Domain\Banner\Enums\BannerStatus;
use App\Domain\Banner\Models\Banner;
use App\Domain\Banner\Models\BannerClaim;
use App\Domain\Product\Actions\ApproveProductRevision;
use App\Domain\Product\Enums\ProductApprovalStatus;
use App\Domain\Product\Enums\ProductOfferStatus;
use App\Domain\Product\Enums\ProductRevisionStatus;
use App\Domain\Product\Enums\ProductStatus;
use App\Domain\Product\Models\Product;
use App\Domain\Product\Models\ProductOffer;
use App\Domain\Product\Models\ProductRevision;
use App\Domain\Product\Models\ProductVariant;
use App\Domain\Store\Models\Store;
use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BannerService
{
    public const CUSTOMER_BANNERS_CACHE_KEY = 'customer:banners:active-ids:v1';

    public function __construct(private readonly ApproveProductRevision $approveProductRevision)
    {
    }

    public function create(Store $store, User $user, array $data): Banner
    {
        $imagePath = $data['image']->store("banners/{$store->id}", 'public');

        return DB::transaction(function () use ($store, $user, $data, $imagePath) {
            $banner = Banner::query()->create([
                'store_id' => $store->id,
                'requested_by' => $user->id,
                'image_url' => $imagePath,
                'discount_label' => $data['discount_label'],
                'date_range' => $data['date_range'] ?? null,
                'cta_label' => $data['cta_label'],
                'terms_of_use' => $data['terms_of_use'],
                'end_time' => $data['end_time'],
                'priority' => 100,
                'is_active' => false,
                'status' => BannerStatus::PENDING,
            ]);

            $this->syncOffers($banner, $data['offer_ids']);
            $this->syncBranches($banner, $data['address_ids']);

            return $this->loadForManagement($banner->fresh());
        });
    }

    public function updateSellerBanner(Banner $banner, array $data): Banner
    {
        return DB::transaction(function () use ($banner, $data) {
            $updates = $this->extractMutableBannerFields($data);

            if (isset($data['image'])) {
                $updates['image_url'] = $data['image']->store("banners/{$banner->store_id}", 'public');
            }

            if ($banner->status === BannerStatus::REJECTED) {
                $updates['status'] = BannerStatus::PENDING;
                $updates['rejection_reason'] = null;
                $updates['is_active'] = false;
            }

            if ($updates !== []) {
                $banner->update($updates);
            }

            if (array_key_exists('offer_ids', $data)) {
                $this->syncOffers($banner, $data['offer_ids']);
            }

            if (array_key_exists('address_ids', $data)) {
                $this->syncBranches($banner, $data['address_ids']);
            }

            return $this->loadForManagement($banner->fresh());
        });
    }

    public function updateAdminBanner(Banner $banner, array $data): Banner
    {
        return DB::transaction(function () use ($banner, $data) {
            $updates = $this->extractMutableBannerFields($data);

            foreach (['priority', 'is_active'] as $field) {
                if (array_key_exists($field, $data)) {
                    $updates[$field] = $data[$field];
                }
            }

            if (isset($data['image'])) {
                $updates['image_url'] = $data['image']->store("banners/{$banner->store_id}", 'public');
            }

            if ($updates !== []) {
                $banner->update($updates);
            }

            if (array_key_exists('offer_ids', $data)) {
                $this->syncOffers($banner, $data['offer_ids']);
            }

            if (array_key_exists('address_ids', $data)) {
                $this->syncBranches($banner, $data['address_ids']);
            }

            $this->clearCustomerCache();

            return $this->loadForManagement($banner->fresh());
        });
    }

    public function approve(Banner $banner, User $admin, array $data = []): Banner
    {
        return DB::transaction(function () use ($banner, $admin, $data) {
            $banner->load('offers.product.pendingRevision');

            foreach ($banner->offers as $offer) {
                $this->approveLinkedOffer($offer, $admin);
            }

            $updates = [
                'status' => BannerStatus::APPROVED,
                'approved_at' => now(),
                'approved_by' => $admin->id,
                'rejection_reason' => null,
                'is_active' => $data['is_active'] ?? true,
            ];

            if (array_key_exists('priority', $data)) {
                $updates['priority'] = $data['priority'];
            }

            $banner->update($updates);
            $this->clearCustomerCache();

            return $this->loadForManagement($banner->fresh());
        });
    }

    public function reject(Banner $banner, string $reason): Banner
    {
        return DB::transaction(function () use ($banner, $reason) {
            $banner->update([
                'status' => BannerStatus::REJECTED,
                'is_active' => false,
                'rejection_reason' => $reason,
            ]);

            $this->clearCustomerCache();

            return $this->loadForManagement($banner->fresh());
        });
    }

    public function activeBanners(?User $user = null): Collection
    {
        $ids = Cache::remember(self::CUSTOMER_BANNERS_CACHE_KEY, now()->addMinutes(15), function () {
            return Banner::query()
                ->where('status', BannerStatus::APPROVED->value)
                ->where('is_active', true)
                ->where('end_time', '>=', now())
                ->whereHas('offers', fn(Builder $query) => $this->eligibleOfferQuery($query))
                ->orderBy('priority')
                ->latest('approved_at')
                ->latest('created_at')
                ->limit(10)
                ->pluck('id')
                ->all();
        });

        if ($ids === []) {
            return collect();
        }

        $order = array_flip($ids);

        return Banner::query()
            ->whereIn('id', $ids)
            ->where('status', BannerStatus::APPROVED->value)
            ->where('is_active', true)
            ->where('end_time', '>=', now())
            ->whereHas('offers', fn(Builder $query) => $this->eligibleOfferQuery($query))
            ->with($this->customerRelations())
            ->withCount('likes')
            ->get()
            ->each(fn(Banner $banner) => $this->attachInteractionFlags($banner, $user))
            ->sortBy(fn(Banner $banner) => $order[$banner->id] ?? PHP_INT_MAX)
            ->values();
    }

    public function loadForCustomer(Banner $banner, ?User $user = null): ?Banner
    {
        if (!$this->isPubliclyVisible($banner)) {
            return null;
        }

        $banner = $banner
            ->load($this->customerRelations())
            ->loadCount('likes');

        $this->attachInteractionFlags($banner, $user);

        if ($this->eligibleOffers($banner)->isEmpty()) {
            return null;
        }

        return $banner;
    }

    public function loadForManagement(Banner $banner): Banner
    {
        return $banner
            ->load([
                'store',
                'requestedBy',
                'approvedBy',
                'branches',
                'offers.product.images',
                'offers.product.variants.attributes',
            ])
            ->loadCount('likes');
    }

    public function selectableOffers(Store $store): Collection
    {
        return ProductOffer::query()
            ->whereHas('product', fn(Builder $query) => $query->where('store_id', $store->id))
            ->with(['product.images', 'product.variants.attributes', 'targets'])
            ->latest()
            ->get();
    }

    public function like(Banner $banner, User $user): Banner
    {
        $banner->likes()->firstOrCreate(['user_id' => $user->id]);

        return $this->loadForCustomer($banner->fresh(), $user) ?? $banner->fresh()->loadCount('likes');
    }

    public function unlike(Banner $banner, User $user): Banner
    {
        $banner->likes()->where('user_id', $user->id)->delete();

        return $this->loadForCustomer($banner->fresh(), $user) ?? $banner->fresh()->loadCount('likes');
    }

    public function favorite(Banner $banner, User $user): Banner
    {
        $banner->favorites()->firstOrCreate(['user_id' => $user->id]);

        return $this->loadForCustomer($banner->fresh(), $user) ?? $banner->fresh()->loadCount('likes');
    }

    public function unfavorite(Banner $banner, User $user): Banner
    {
        $banner->favorites()->where('user_id', $user->id)->delete();

        return $this->loadForCustomer($banner->fresh(), $user) ?? $banner->fresh()->loadCount('likes');
    }

    public function createClaim(Banner $banner, User $user): BannerClaim
    {
        $banner = $this->loadForCustomer($banner, $user);

        if (!$banner) {
            throw new \DomainException('This banner is not available for claiming.');
        }

        $eligibleOffers = $this->eligibleOffers($banner);

        if ($eligibleOffers->isEmpty()) {
            throw new \DomainException('This banner has no eligible offers to claim.');
        }

        $now = now();
        $expiresAt = collect([$banner->end_time])
            ->concat($eligibleOffers->map(function (ProductOffer $offer) use ($now) {
                return $offer->claim_expiration_minutes
                    ? $now->copy()->addMinutes($offer->claim_expiration_minutes)
                    : null;
            }))
            ->filter()
            ->sortBy(fn($date) => $date->getTimestamp())
            ->first();

        $claim = BannerClaim::query()->create([
            'banner_id' => $banner->id,
            'user_id' => $user->id,
            'store_id' => $banner->store_id,
            'status' => BannerClaimStatus::ACTIVE,
            'claim_token' => Str::random(64),
            'qr_code_token' => Str::random(64),
            'claim_snapshot' => $this->claimSnapshot($banner, $eligibleOffers, $now),
            'expires_at' => $expiresAt,
        ]);

        return $claim->fresh(['banner', 'store', 'user']);
    }

    public function clearCustomerCache(): void
    {
        Cache::forget(self::CUSTOMER_BANNERS_CACHE_KEY);
    }

    public function eligibleOffers(Banner $banner): Collection
    {
        return $banner->offers
            ->filter(fn(ProductOffer $offer) => $this->isEligibleOffer($offer))
            ->values();
    }

    private function syncOffers(Banner $banner, array $offerIds): void
    {
        $banner->offers()->sync(array_values(array_unique($offerIds)));
    }

    private function syncBranches(Banner $banner, array $addressIds): void
    {
        $banner->branches()->sync(array_values(array_unique($addressIds)));
    }

    private function extractMutableBannerFields(array $data): array
    {
        $fields = [
            'discount_label',
            'date_range',
            'cta_label',
            'terms_of_use',
            'end_time',
        ];

        $updates = [];

        foreach ($fields as $field) {
            if (array_key_exists($field, $data)) {
                $updates[$field] = $data[$field];
            }
        }

        return $updates;
    }

    private function approveLinkedOffer(ProductOffer $offer, User $admin): void
    {
        /** @var Product|null $product */
        $product = $offer->product;

        if (!$product) {
            return;
        }

        /** @var ProductRevision|null $pendingRevision */
        $pendingRevision = $product->pendingRevision;

        if ($pendingRevision && $pendingRevision->status === ProductRevisionStatus::PENDING) {
            $this->approveProductRevision->execute($pendingRevision, $admin, 'Approved through banner approval.');
            $product = $product->fresh('offer');
            $offer = $product->offer ?? $offer->fresh();
        }

        if (
            $product->status !== ProductStatus::ACTIVE
            || $product->approval_status !== ProductApprovalStatus::APPROVED
        ) {
            $product->update([
                'status' => ProductStatus::ACTIVE,
                'approval_status' => ProductApprovalStatus::APPROVED,
                'approved_at' => now(),
                'approved_by' => $admin->id,
                'rejected_at' => null,
                'rejected_by' => null,
                'rejection_reason' => null,
            ]);
        }

        $offerUpdates = ['status' => ProductOfferStatus::ACTIVE];

        if ($offer->starts_at === null) {
            $offerUpdates['starts_at'] = now();
        }

        if ($offer->ends_at === null && ($offer->duration_days || $offer->duration_hours)) {
            $endsAt = now();

            if ($offer->duration_days) {
                $endsAt = $endsAt->addDays($offer->duration_days);
            }

            if ($offer->duration_hours) {
                $endsAt = $endsAt->addHours($offer->duration_hours);
            }

            $offerUpdates['ends_at'] = $endsAt;
        }

        $offer->update($offerUpdates);
    }

    private function eligibleOfferQuery($query)
    {
        return $query
            ->where('status', ProductOfferStatus::ACTIVE->value)
            ->where(function (Builder $dateQuery) {
                $dateQuery->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function (Builder $dateQuery) {
                $dateQuery->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            })
            ->whereHas('product', function (Builder $productQuery) {
                $productQuery
                    ->where('status', ProductStatus::ACTIVE->value)
                    ->where('approval_status', ProductApprovalStatus::APPROVED->value);
            });
    }

    private function isEligibleOffer(ProductOffer $offer): bool
    {
        $now = now();
        $product = $offer->product;

        return $product
            && $product->status === ProductStatus::ACTIVE
            && $product->approval_status === ProductApprovalStatus::APPROVED
            && $offer->status === ProductOfferStatus::ACTIVE
            && ($offer->starts_at === null || $offer->starts_at->lte($now))
            && ($offer->ends_at === null || $offer->ends_at->gte($now));
    }

    private function isPubliclyVisible(Banner $banner): bool
    {
        return $banner->status === BannerStatus::APPROVED
            && $banner->is_active
            && $banner->end_time
            && $banner->end_time->gte(now());
    }

    private function customerRelations(): array
    {
        return [
            'store.categories',
            'store.socials',
            'store.hours',
            'branches',
            'offers' => fn($query) => $this->eligibleOfferQuery($query),
            'offers.targets',
            'offers.product.images',
            'offers.product.variants' => fn($query) => $query->where('is_active', true)->with('attributes'),
        ];
    }

    private function attachInteractionFlags(Banner $banner, ?User $user): void
    {
        if (!$user) {
            $banner->setAttribute('is_liked', false);
            $banner->setAttribute('is_favorited', false);

            return;
        }

        $banner->setAttribute('is_liked', $banner->likes()->where('user_id', $user->id)->exists());
        $banner->setAttribute('is_favorited', $banner->favorites()->where('user_id', $user->id)->exists());
    }

    private function claimSnapshot(Banner $banner, Collection $offers, \DateTimeInterface $claimedAt): array
    {
        return [
            'claimed_at' => $claimedAt->format(DATE_ATOM),
            'banner' => [
                'id' => $banner->id,
                'image_url' => $banner->image_url,
                'discount_label' => $banner->discount_label,
                'date_range' => $banner->date_range,
                'cta_label' => $banner->cta_label,
                'terms_of_use' => $banner->terms_of_use,
                'end_time' => $banner->end_time?->toIso8601String(),
            ],
            'store' => [
                'id' => $banner->store?->id,
                'name' => $banner->store?->name,
                'description' => $banner->store?->description,
                'logo_url' => $banner->store?->logo_url,
                'banner_url' => $banner->store?->banner_url,
                'phone' => $banner->store?->phone,
                'email' => $banner->store?->email,
            ],
            'branches' => $banner->branches
                ->map(fn($address) => [
                    'id' => $address->id,
                    'address_line1' => $address->address_line1,
                    'city' => $address->city,
                    'latitude' => $address->latitude,
                    'longitude' => $address->longitude,
                ])
                ->values()
                ->all(),
            'offers' => $offers
                ->map(fn(ProductOffer $offer) => $this->snapshotOffer($offer))
                ->values()
                ->all(),
        ];
    }

    private function snapshotOffer(ProductOffer $offer): array
    {
        return [
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
            'product' => [
                'id' => $offer->product?->id,
                'store_id' => $offer->product?->store_id,
                'title' => $offer->product?->title,
                'currency' => $offer->product?->currency,
                'base_price' => $offer->product?->base_price,
            ],
            'variants' => $offer->product?->variants
                ->filter(fn(ProductVariant $variant) => $variant->is_active)
                ->map(fn(ProductVariant $variant) => [
                    'id' => $variant->id,
                    'sku' => $variant->sku,
                    'title' => $variant->title,
                    'price' => $variant->price,
                    'currency' => $variant->currency,
                    'inventory_mode' => $variant->inventory_mode?->value ?? $variant->inventory_mode,
                ])
                ->values()
                ->all() ?? [],
        ];
    }
}
