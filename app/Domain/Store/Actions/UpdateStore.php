<?php

namespace App\Domain\Store\Actions;

use App\Domain\Store\DTOs\StoreData;
use App\Domain\Store\Events\StoreUpdated;
use App\Domain\Store\Models\Store;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\Storage;

class UpdateStore
{
    public function execute(Store $store, User $user, StoreData $data): Store
    {
        // Check if user owns the store
        if ($store->owner_user_id !== $user->id) {
            throw new \Exception('You are not authorized to update this store.');
        }

        // Check if store can be updated (not approved)
        if ($store->status === 'active') {
            throw new \Exception('Cannot update an approved store. Please contact support.');
        }

        // Update store basic information
        $store->update([
            'name' => $data->name,
            'description' => $data->description,
            'email' => $data->email,
            'phone' => $data->phone,
            'tax_id' => $data->tax_id ?? $store->tax_id,
            'subscription_tier' => $data->subscription_tier ?? $store->subscription_tier,
        ]);

        // Update logo if provided
        if ($data->logo_url) {
            // Delete old logo if exists
            if ($store->logo_url && Storage::disk('public')->exists($store->logo_url)) {
                Storage::disk('public')->delete($store->logo_url);
            }
            $store->update(['logo_url' => $data->logo_url]);
        }

        // Update banner if provided
        if ($data->banner_url) {
            // Delete old banner if exists
            if ($store->banner_url && Storage::disk('public')->exists($store->banner_url)) {
                Storage::disk('public')->delete($store->banner_url);
            }
            $store->update(['banner_url' => $data->banner_url]);
        }

        // Update categories if provided
        if ($data->category_ids) {
            $store->categories()->sync($data->category_ids);
        }

        // Update address if provided
        if ($data->address) {
            $address = $store->addresses()->first();
            if ($address) {
                $address->update([
                    'address_line1' => $data->address['address_line1'] ?? $address->address_line1,
                    'address_line2' => $data->address['address_line2'] ?? $address->address_line2,
                    'city' => $data->address['city'] ?? $address->city,
                    'state' => $data->address['state'] ?? $address->state,
                    'postal_code' => $data->address['postal_code'] ?? $address->postal_code,
                    'country' => $data->address['country'] ?? $address->country,
                    'latitude' => $data->address['latitude'] ?? $address->latitude,
                    'longitude' => $data->address['longitude'] ?? $address->longitude,
                ]);
            }
        }

        // If store was rejected, reset to pending
        if ($store->status === 'rejected') {
            $store->update([
                'status' => 'pending',
                'rejected_at' => null,
                'rejected_by' => null,
                'rejection_reason' => null,
            ]);
        }

        // Dispatch event
        event(new StoreUpdated($store, $user));

        return $store->fresh(['owner', 'categories', 'addresses', 'verifications']);
    }
}
