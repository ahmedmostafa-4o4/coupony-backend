<?php

namespace App\Application\Http\Requests\Concerns;

use App\Domain\Product\Models\ProductOffer;
use App\Domain\Store\Models\Store;
use Illuminate\Validation\Validator;

trait ValidatesBannerStorePayload
{
    protected function validateBannerStorePayload(
        Validator $validator,
        Store $store,
        bool $requireOffers = false,
        bool $requireBranches = false
    ): void {
        $offerIds = collect($this->input('offer_ids', []))->filter()->unique()->values();
        $addressIds = collect($this->input('address_ids', []))->filter()->unique()->values();

        if (($requireOffers || $this->has('offer_ids')) && $offerIds->isEmpty()) {
            $validator->errors()->add('offer_ids', 'At least one offer is required.');
        }

        if (($requireBranches || $this->has('address_ids')) && $addressIds->isEmpty()) {
            $validator->errors()->add('address_ids', 'At least one branch is required.');
        }

        if ($offerIds->isNotEmpty()) {
            $matchingOfferCount = ProductOffer::query()
                ->whereIn('id', $offerIds)
                ->whereHas('product', fn ($query) => $query->where('store_id', $store->id))
                ->count();

            if ($matchingOfferCount !== $offerIds->count()) {
                $validator->errors()->add('offer_ids', 'All selected offers must belong to this store.');
            }
        }

        if ($addressIds->isNotEmpty()) {
            $matchingAddressCount = $store->addresses()
                ->whereIn('addresses.id', $addressIds)
                ->count();

            if ($matchingAddressCount !== $addressIds->count()) {
                $validator->errors()->add('address_ids', 'All selected branches must belong to this store.');
            }
        }
    }
}
