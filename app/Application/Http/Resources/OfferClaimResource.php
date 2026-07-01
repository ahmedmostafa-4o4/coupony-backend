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
            'offer_snapshot' => $this->offer_snapshot,
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
