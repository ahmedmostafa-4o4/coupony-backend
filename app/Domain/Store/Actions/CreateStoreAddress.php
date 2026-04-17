<?php

namespace App\Domain\Store\Actions;

use App\Domain\Store\Models\Store;
use App\Domain\User\Models\Address;
use Illuminate\Support\Facades\DB;

class CreateStoreAddress
{
    public function execute(Store $store, array $validated): Address
    {
        $address = Address::create($this->extractAddressFields($validated));

        $pivotFields = $this->extractPivotFields($validated, true);

        $store->addresses()->attach($address->id, $pivotFields);
        $this->syncDefaultFlags($store, $address->id, $pivotFields);

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

    private function extractPivotFields(array $validated, bool $withDefaults = false): array
    {
        $pivotFields = [];

        if (array_key_exists('label', $validated) || $withDefaults) {
            $pivotFields['label'] = $validated['label'] ?? 'branch';
        }

        if (array_key_exists('is_default_shipping', $validated) || $withDefaults) {
            $pivotFields['is_default_shipping'] = (bool) ($validated['is_default_shipping'] ?? false);
        }

        if (array_key_exists('is_default_billing', $validated) || $withDefaults) {
            $pivotFields['is_default_billing'] = (bool) ($validated['is_default_billing'] ?? false);
        }

        return $pivotFields;
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
