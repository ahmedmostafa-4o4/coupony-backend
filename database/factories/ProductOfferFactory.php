<?php

namespace Database\Factories;

use App\Domain\Product\Enums\ProductOfferStatus;
use App\Domain\Product\Enums\ProductOfferType;
use App\Domain\Product\Models\Product;
use App\Domain\Product\Models\ProductOffer;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductOfferFactory extends Factory
{
    protected $model = ProductOffer::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'type' => ProductOfferType::FIXED,
            'status' => ProductOfferStatus::ACTIVE,
            'label' => 'Fixed discount',
            'terms_en' => null,
            'terms_ar' => null,
            'branch_only' => false,
            'starts_at' => null,
            'ends_at' => null,
            'claim_expiration_minutes' => 1440,
            'max_claims_per_user' => null,
            'max_total_claims' => null,
            'fixed_amount' => 10,
            'percentage_value' => null,
            'max_discount' => null,
            'buy_qty' => null,
            'get_qty' => null,
            'allow_mix_buy_variants' => false,
            'allow_mix_reward_variants' => false,
        ];
    }
}
