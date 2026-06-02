<?php

namespace Tests\Unit\Domain\Explore;

use App\Domain\Explore\Support\DiscountCalculator;
use App\Domain\Product\Enums\ProductOfferType;
use App\Domain\Product\Models\ProductOffer;
use Faker\Factory as Faker;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

class DiscountCalculatorPropertyTest extends TestCase
{
    /**
     * Property 11: Discount Calculation Correctness (Percentage Type)
     * Validates: Requirements 16.6, 16.7
     *
     * For any product with base_price > 0 and a percentage-type offer,
     * discount_percent SHALL equal percentage_value and
     * discounted_price SHALL equal base_price * (1 - percentage_value/100).
     */
    #[DataProvider('percentageTypeRandomCombinationsProvider')]
    #[Group('explore-page')]
    public function test_property_percentage_type_discount_calculation(
        float $basePrice,
        float $percentageValue
    ): void {
        $offer = new ProductOffer();
        $offer->type = ProductOfferType::PERCENTAGE;
        $offer->percentage_value = $percentageValue;
        $offer->fixed_amount = null;

        [$discountPercent, $discountedPrice] = DiscountCalculator::calculate($offer, $basePrice);

        $expectedDiscountPercent = $percentageValue;
        $expectedDiscountedPrice = round($basePrice * (1 - $percentageValue / 100), 2);

        $this->assertEqualsWithDelta(
            $expectedDiscountPercent,
            $discountPercent,
            0.0001,
            "Percentage discount_percent mismatch for base_price=$basePrice, percentage_value=$percentageValue"
        );

        $this->assertEqualsWithDelta(
            $expectedDiscountedPrice,
            $discountedPrice,
            0.01,
            "Percentage discounted_price mismatch for base_price=$basePrice, percentage_value=$percentageValue"
        );
    }

    /**
     * Property 11: Discount Calculation Correctness (Fixed Type)
     * Validates: Requirements 16.6, 16.7
     *
     * For any product with base_price > 0 and a fixed-type offer,
     * discount_percent SHALL equal (fixed_amount / base_price) * 100 and
     * discounted_price SHALL equal base_price - fixed_amount.
     */
    #[DataProvider('fixedTypeRandomCombinationsProvider')]
    #[Group('explore-page')]
    public function test_property_fixed_type_discount_calculation(
        float $basePrice,
        float $fixedAmount
    ): void {
        $offer = new ProductOffer();
        $offer->type = ProductOfferType::FIXED;
        $offer->fixed_amount = $fixedAmount;
        $offer->percentage_value = null;

        [$discountPercent, $discountedPrice] = DiscountCalculator::calculate($offer, $basePrice);

        $expectedDiscountPercent = round(($fixedAmount / $basePrice) * 100, 2);
        $expectedDiscountedPrice = round($basePrice - $fixedAmount, 2);

        $this->assertEqualsWithDelta(
            $expectedDiscountPercent,
            $discountPercent,
            0.01,
            "Fixed discount_percent mismatch for base_price=$basePrice, fixed_amount=$fixedAmount"
        );

        $this->assertEqualsWithDelta(
            $expectedDiscountedPrice,
            $discountedPrice,
            0.01,
            "Fixed discounted_price mismatch for base_price=$basePrice, fixed_amount=$fixedAmount"
        );
    }

    /**
     * @return array<string, array{float, float}>
     */
    public static function percentageTypeRandomCombinationsProvider(): array
    {
        $faker = Faker::create();
        $datasets = [];

        for ($i = 0; $i < 100; $i++) {
            $basePrice = $faker->randomFloat(2, 0.01, 10000.00);
            $percentageValue = $faker->randomFloat(2, 0, 100);

            $datasets["percentage_$i"] = [
                $basePrice,
                $percentageValue,
            ];
        }

        return $datasets;
    }

    /**
     * @return array<string, array{float, float}>
     */
    public static function fixedTypeRandomCombinationsProvider(): array
    {
        $faker = Faker::create();
        $datasets = [];

        for ($i = 0; $i < 100; $i++) {
            $basePrice = $faker->randomFloat(2, 1.00, 10000.00);
            // fixed_amount should be less than or equal to base_price for realistic scenarios
            $fixedAmount = $faker->randomFloat(2, 0.01, $basePrice);

            $datasets["fixed_$i"] = [
                $basePrice,
                $fixedAmount,
            ];
        }

        return $datasets;
    }
}
