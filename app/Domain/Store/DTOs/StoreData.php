<?php

namespace App\Domain\Store\DTOs;

use App\Application\Http\Requests\createStoreRequest;
use Str;

class StoreData
{

    /**
     * Create a new class instance.
     */
    public function __construct(
        public readonly string $name,
        public readonly string $description,
        public readonly string $ownerUserId,
        public readonly ?string $address_line1 = null,
        public readonly ?string $address_line2 = null,
        public readonly ?string $city = null,
        public readonly ?string $latitude = null,
        public readonly ?string $longitude = null,
        public readonly ?string $label = null,
        public readonly ?string $subscriptionTier = null,
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
        public readonly ?array $categories = []

    ) {
    }

    public static function fromRequest(createStoreRequest $request): self
    {
        // عمل slug من اسم المتجر لتجنب المسافات والرموز
        $storeNameSlug = Str::slug($request->input('name'));

        // الملفات
        $logoPath = $request->file('logo_url')
            ? $request->file('logo_url')->store("stores/{$request->user()->id}/{$storeNameSlug}/logo", 'public')
            : null;

        $bannerPath = $request->file('banner_url')
            ? $request->file('banner_url')->store("stores/{$request->user()->id}/{$storeNameSlug}/banner", 'public')
            : null;

        $commercialRegisterPath = $request->file('verification_docs.commercial_register')
            ? $request->file('verification_docs.commercial_register')->store("stores/verifications/{$request->user()->id}/{$storeNameSlug}/commercial_register", 'public')
            : null;

        $taxCardPath = $request->file('verification_docs.tax_card')
            ? $request->file('verification_docs.tax_card')->store("stores/verifications/{$request->user()->id}/{$storeNameSlug}/tax_card", 'public')
            : null;

        $idCardFrontPath = $request->file('verification_docs.id_card_front')
            ? $request->file('verification_docs.id_card_front')->store("stores/verifications/{$request->user()->id}/{$storeNameSlug}/id_card_front", 'public')
            : null;

        $idCardBackPath = $request->file('verification_docs.id_card_back')
            ? $request->file('verification_docs.id_card_back')->store("stores/verifications/{$request->user()->id}/{$storeNameSlug}/id_card_back", 'public')
            : null;
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
            logo_url: $logoPath,
            banner_url: $bannerPath,
            commercial_register: $commercialRegisterPath,
            tax_card: $taxCardPath,
            id_card_front: $idCardFrontPath,
            id_card_back: $idCardBackPath,
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
}
