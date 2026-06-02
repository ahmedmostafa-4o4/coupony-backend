<?php

namespace Database\Factories\Domain\Product\Models;

use App\Domain\Product\Enums\OfferClaimStatus;
use App\Domain\Product\Models\OfferClaim;
use App\Domain\Product\Models\Product;
use App\Domain\Product\Models\ProductOffer;
use App\Domain\Store\Models\Store;
use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class OfferClaimFactory extends Factory
{
    protected $model = OfferClaim::class;

    public function definition(): array
    {
        return [
            'id' => Str::uuid()->toString(),
            'user_id' => User::factory(),
            'store_id' => Store::factory(),
            'product_id' => Product::factory(),
            'offer_id' => ProductOffer::factory(),
            'status' => OfferClaimStatus::ACTIVE,
            'claim_token' => Str::random(10),
            'qr_code_token' => Str::random(10),
            'offer_snapshot' => [],
            'expires_at' => now()->addDays(7),
        ];
    }
}
