<?php

namespace App\Domain\Store\Actions;

use App\Domain\Store\Models\Store;
use App\Domain\User\Models\Address;
use Illuminate\Support\Facades\DB;

class UpdateStoreAddress
{
    public function execute(Store $store, Address $address, array $validated): Address
    {
        $addressFields = $this->extractAddressFields($validated);

        if ($addressFields !== []) {
            $address->update($addressFields);
        }

        $pivotFields = $this->extractPivotFields($validated);

        if ($pivotFields !== []) {
            $store->addresses()->updateExistingPivot($address->id, $pivotFields);
            $this->syncDefaultFlags($store, $address->id, $pivotFields);
        }

        return $store->addresses()->whereKey($address->id)->firstOrFail();
    }

    private function extractAddressFields(array $validated): array
    {
        return collect($validated)->only([
            'first_name',
            'last_name',
            'company',
            'address_line1',
            'address_line2',
            'city',
            'state_province',
            'postal_code',
            'country_code',
            'phone_number',
            'latitude',
            'longitude',
            'delivery_instructions',
        ])->all();
    }

    private function extractPivotFields(array $validated): array
    {
        return collect($validated)->only([
            'label',
            'is_default_shipping',
            'is_default_billing',
        ])->map(function ($value, string $key) {
            if (in_array($key, ['is_default_shipping', 'is_default_billing'], true)) {
                return (bool) $value;
            }

            return $value;
        })->all();
    }

    private function syncDefaultFlags(Store $store, int $addressId, array $pivotFields): void
    {
        if (($pivotFields['is_default_shipping'] ?? false) === true) {
            DB::table('addressables')
                ->where('owner_type', $store::class)
                ->where('owner_id', $store->id)
                ->where('address_id', '!=', $addressId)
                ->update(['is_default_shipping' => false]);
        }

        if (($pivotFields['is_default_billing'] ?? false) === true) {
            DB::table('addressables')
                ->where('owner_type', $store::class)
                ->where('owner_id', $store->id)
                ->where('address_id', '!=', $addressId)
                ->update(['is_default_billing' => false]);
        }
    }
}
