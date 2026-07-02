<?php

namespace App\Application\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class OfferClaimResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'store_id' => $this->store_id,
            'product_id' => $this->product_id,
            'offer_id' => $this->offer_id,
            'status' => $this->status?->value ?? $this->status,
            'claim_token' => $this->claim_token,
            'qr_code_token' => $this->qr_code_token,
            'offer_snapshot' => $this->normalizedOfferSnapshot(),
            'customer' => $this->customerData(),
            'product' => $this->productData(),
            'store' => $this->storeData(),
            'usage_count' => (int) ($this->usage_count ?? 0),
            'expires_at' => $this->expires_at?->toIso8601String(),
            'redeemed_at' => $this->redeemed_at?->toIso8601String(),
            'redeemed_by' => $this->redeemed_by,
            'revenue_amount' => $this->revenue_amount,
            'revenue_currency' => $this->revenue_currency,
            'is_expired' => $this->isExpired(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    private function storeData(): ?array
    {
        $snapshotStore = data_get($this->offer_snapshot, 'store');

        if (is_array($snapshotStore)) {
            return $snapshotStore;
        }

        if (! $this->relationLoaded('store') || ! $this->store) {
            return null;
        }

        return [
            'id' => $this->store->id,
            'name' => $this->store->name,
            'logo_url' => $this->publicStorageUrl($this->store->logo_url),
        ];
    }

    private function normalizedOfferSnapshot(): array
    {
        $snapshot = is_array($this->offer_snapshot) ? $this->offer_snapshot : [];
        $snapshotOffer = data_get($snapshot, 'offer');
        $fallbackOffer = $this->offerData();

        if ($fallbackOffer !== null) {
            $snapshot['offer'] = is_array($snapshotOffer)
                ? array_replace($fallbackOffer, $snapshotOffer)
                : $fallbackOffer;
        }

        return $snapshot;
    }

    private function offerData(): ?array
    {
        if (! $this->relationLoaded('offer') || ! $this->offer) {
            return null;
        }

        return [
            'id' => $this->offer->id,
            'type' => $this->offer->type?->value ?? $this->offer->type,
            'status' => $this->offer->status?->value ?? $this->offer->status,
            'label' => $this->offer->label,
            'terms' => $this->localizedTerms(),
            'terms_en' => $this->normalizedTerms($this->offer->terms_en),
            'terms_ar' => $this->normalizedTerms($this->offer->terms_ar),
            'branch_only' => (bool) $this->offer->branch_only,
            'fixed_amount' => $this->offer->fixed_amount,
            'percentage_value' => $this->offer->percentage_value,
            'max_discount' => $this->offer->max_discount,
            'currency' => $this->offerCurrency(),
            'buy_qty' => $this->offer->buy_qty,
            'get_qty' => $this->offer->get_qty,
            'starts_at' => $this->offer->starts_at?->toIso8601String(),
            'ends_at' => $this->offer->ends_at?->toIso8601String(),
            'claim_expiration_minutes' => $this->offer->claim_expiration_minutes,
        ];
    }

    private function localizedTerms(): array
    {
        $isArabic = str_starts_with(strtolower(app()->getLocale()), 'ar');
        $preferred = $isArabic ? $this->offer->terms_ar : $this->offer->terms_en;
        $fallback = $isArabic ? $this->offer->terms_en : $this->offer->terms_ar;

        return $this->normalizedTerms($preferred ?: $fallback ?: []);
    }

    private function normalizedTerms(?array $terms): array
    {
        return collect($terms ?? [])
            ->filter(fn ($term) => is_string($term) && $term !== '')
            ->values()
            ->all();
    }

    private function offerCurrency(): ?string
    {
        $snapshotCurrency = data_get($this->offer_snapshot, 'product.currency');

        if (is_string($snapshotCurrency) && $snapshotCurrency !== '') {
            return $snapshotCurrency;
        }

        if ($this->relationLoaded('product') && $this->product) {
            return $this->product->currency;
        }

        return null;
    }

    private function customerData(): ?array
    {
        $snapshotCustomer = data_get($this->offer_snapshot, 'customer');

        if (is_array($snapshotCustomer)) {
            return $snapshotCustomer;
        }

        if (! $this->relationLoaded('user') || ! $this->user) {
            return null;
        }

        return [
            'id' => $this->user->id,
            'name' => $this->user->full_name,
        ];
    }

    private function productData(): ?array
    {
        $snapshotProduct = data_get($this->offer_snapshot, 'product');

        if (is_array($snapshotProduct)) {
            return $snapshotProduct;
        }

        if (! $this->relationLoaded('product') || ! $this->product) {
            return null;
        }

        $primaryImage = $this->product->relationLoaded('images')
            ? $this->product->images->firstWhere('is_primary', true)
            : null;

        return [
            'id' => $this->product->id,
            'title' => $this->product->title,
            'image_url' => $this->publicStorageUrl($primaryImage?->image_url),
        ];
    }

    private function publicStorageUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return Storage::disk('public')->url($path);
    }
}
