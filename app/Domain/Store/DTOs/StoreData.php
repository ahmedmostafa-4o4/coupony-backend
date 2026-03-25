<?php

namespace App\Domain\Store\DTOs;

use App\Application\Http\Requests\createStoreRequest;
use App\Application\Http\Requests\updateStoreRequest;
use Illuminate\Support\Str;
use Log;

class StoreData
{

    /**
     * Create a new class instance.
     */
    public function __construct(
        public readonly string $name,
        public readonly string $description,
        public readonly ?string $ownerUserId = null,
        public readonly ?string $address_line1 = null,
        public readonly ?string $address_line2 = null,
        public readonly ?string $city = null,
        public readonly ?string $latitude = null,
        public readonly ?string $longitude = null,
        public readonly ?string $label = null,
        public readonly ?string $subscriptionTier = null,
        public readonly ?string $subscription_tier = null,
        public readonly ?string $status = null,
        public readonly ?string $phone = null,
        public readonly ?string $email = null,
        public readonly ?string $tax_id = null,
        public readonly ?string $logo_url = null,
        public readonly ?string $banner_url = null,
        public readonly ?string $commercial_register = null,
        public readonly ?string $tax_card = null,
        public readonly ?string $id_card_front = null,
        public readonly ?string $id_card_back = null,
        public readonly ?array $categories = [],
        public readonly ?array $category_ids = [],
        public readonly ?array $address = null

    ) {
    }

    public static function fromRequest(createStoreRequest $request): self
    {
        // Don't upload files yet - will be handled after store creation
        return new self(
            name: $request->input('name'),
            description: $request->input('description', ''),
            ownerUserId: $request->user()->id,
            subscriptionTier: $request->input('subscription_tier'),
            address_line1: $request->input('address_line1'),
            address_line2: $request->input('address_line2'),
            city: $request->input('city'),
            latitude: $request->input('latitude'),
            longitude: $request->input('longitude'),
            label: $request->input('label'),
            categories: $request->input('categories'),
            status: $request->input('status', 'pending'),
            phone: $request->input('phone'),
            email: $request->user()->email,
            tax_id: $request->input('tax_id'),
            logo_url: null,
            banner_url: null,
            commercial_register: null,
            tax_card: null,
            id_card_front: null,
            id_card_back: null,
        );
    }

    public function toArray(): array
    {

        return [
            'name' => $this->name,
            'description' => $this->description,
            'owner_user_id' => $this->ownerUserId,
            'subscription_tier' => $this->subscriptionTier,
            'categories' => $this->categories,
            'address_line1' => $this->address_line1,
            'address_line2' => $this->address_line2,
            'city' => $this->city,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'label' => $this->label,
            'status' => $this->status,
            'phone' => $this->phone,
            'email' => $this->email,
            'tax_id' => $this->tax_id,
            'logo_url' => $this->logo_url,
            'banner_url' => $this->banner_url,
            'commercial_register' => $this->commercial_register,
            'tax_card' => $this->tax_card,
            'id_card_front' => $this->id_card_front,
            'id_card_back' => $this->id_card_back,
        ];
    }

    public static function fromUpdateRequest(updateStoreRequest $request, ?string $storeId = null): self
    {
        $logoPath = null;
        $bannerPath = null;

        if ($request->hasFile('logo') && $storeId) {
            $logoPath = $request->file('logo')->store("stores/{$storeId}/logo", 'public');
        }

        if ($request->hasFile('banner') && $storeId) {
            $bannerPath = $request->file('banner')->store("stores/{$storeId}/banner", 'public');
        }
        Log::info($request->all());
        return new self(
            name: $request->input('name', ''),
            description: $request->input('description', ''),
            ownerUserId: $request->user()->id,
            subscription_tier: $request->input('subscription_tier'),
            address: $request->input('address'),
            category_ids: $request->input('category_ids'),
            phone: $request->input('phone'),
            email: $request->input('email'),
            tax_id: $request->input('tax_id'),
            logo_url: $logoPath,
            banner_url: $bannerPath,
        );
    }
}
