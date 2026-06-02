<?php

namespace App\Domain\Explore\Support;

use App\Domain\Product\Enums\ProductOfferType;
use App\Domain\Product\Models\ProductOffer;

class DiscountCalculator
{
    /**
     * Calculate discount percentage and discounted price from an offer.
     * - For percentage-type offers: uses percentage_value directly
     * - For fixed-type offers: calculates (fixed_amount / base_price) * 100
     *
     * Returns [discount_percent, discounted_price]
     */
    public static function calculate(ProductOffer $offer, float $basePrice): array
    {
        if ($basePrice <= 0) {
            return [0.0, 0.0];
        }

        return match ($offer->type) {
            ProductOfferType::PERCENTAGE => self::calculatePercentage($offer, $basePrice),
            ProductOfferType::FIXED => self::calculateFixed($offer, $basePrice),
            default => [0.0, $basePrice],
        };
    }

    private static function calculatePercentage(ProductOffer $offer, float $basePrice): array
    {
        $percentageValue = (float) ($offer->percentage_value ?? 0);
        $discountPercent = $percentageValue;
        $discountedPrice = $basePrice * (1 - $percentageValue / 100);

        return [$discountPercent, round($discountedPrice, 2)];
    }

    private static function calculateFixed(ProductOffer $offer, float $basePrice): array
    {
        $fixedAmount = (float) ($offer->fixed_amount ?? 0);
        $discountPercent = ($fixedAmount / $basePrice) * 100;
        $discountedPrice = $basePrice - $fixedAmount;

        return [round($discountPercent, 2), round($discountedPrice, 2)];
    }
}
