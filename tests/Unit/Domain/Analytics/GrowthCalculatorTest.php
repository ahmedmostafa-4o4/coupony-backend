<?php

namespace Tests\Unit\Domain\Analytics;

use App\Domain\Analytics\Services\GrowthCalculator;
use Faker\Factory as Faker;
use PHPUnit\Framework\TestCase;

/**
 * Property-based test for GrowthCalculator.
 *
 * Property 1: Growth Percentage Calculation Safety
 * Validates: Requirements 3.2, 3.3, 4.2, 4.3, 14.1, 14.2, 14.3, 14.4
 */
class GrowthCalculatorTest extends TestCase
{
    /**
     * @dataProvider randomCurrentPreviousPairsWithPositivePrevious
     *
     * Property: When previous > 0, result equals round(((current - previous) / previous) * 100, 1)
     * Validates: Requirements 3.2, 4.2, 14.1
     */
    public function test_growth_calculation_matches_formula_when_previous_is_positive(int|float $current, int|float $previous): void
    {
        $result = GrowthCalculator::calculate($current, $previous);

        $expected = round((($current - $previous) / $previous) * 100, 1);

        $this->assertSame($expected, $result, "For current={$current}, previous={$previous}: expected {$expected}, got {$result}");
    }

    /**
     * @dataProvider randomCurrentValuesWithZeroPrevious
     *
     * Property: When previous == 0, result is always 0.0 (regardless of current value)
     * Validates: Requirements 3.3, 4.3, 14.2, 14.3
     */
    public function test_returns_zero_when_previous_is_zero(int|float $current): void
    {
        $result = GrowthCalculator::calculate($current, 0);

        $this->assertSame(0.0, $result, "For current={$current}, previous=0: expected 0.0, got {$result}");
    }

    /**
     * @dataProvider randomNegativeGrowthPairs
     *
     * Property: Supports negative growth (current < previous produces negative result)
     * Validates: Requirements 14.4
     */
    public function test_supports_negative_growth_when_current_less_than_previous(int|float $current, int|float $previous): void
    {
        $result = GrowthCalculator::calculate($current, $previous);

        $this->assertLessThan(0.0, $result, "For current={$current} < previous={$previous}: expected negative result, got {$result}");
    }

    /**
     * @dataProvider allRandomPairs
     *
     * Property: Result is always a float rounded to 1 decimal place
     * Validates: Requirements 14.1
     */
    public function test_result_is_always_float_rounded_to_one_decimal(int|float $current, int|float $previous): void
    {
        $result = GrowthCalculator::calculate($current, $previous);

        $this->assertIsFloat($result);
        $this->assertSame(round($result, 1), $result, "Result {$result} is not rounded to 1 decimal place");
    }

    /**
     * Edge case: both current and previous are zero.
     * Validates: Requirements 14.2
     */
    public function test_returns_zero_when_both_values_are_zero(): void
    {
        $result = GrowthCalculator::calculate(0, 0);

        $this->assertSame(0.0, $result);
    }

    /**
     * Edge case: previous is zero with positive current.
     * Validates: Requirements 14.3
     */
    public function test_returns_zero_when_previous_zero_and_current_positive(): void
    {
        $result = GrowthCalculator::calculate(100, 0);

        $this->assertSame(0.0, $result);
    }

    /**
     * Edge case: negative growth scenario with known values.
     * Validates: Requirements 14.4
     */
    public function test_negative_growth_with_known_values(): void
    {
        // current=50, previous=100 → ((50-100)/100)*100 = -50.0
        $result = GrowthCalculator::calculate(50, 100);

        $this->assertSame(-50.0, $result);
    }

    /**
     * Generates 100+ random (current, previous) pairs where previous > 0.
     */
    public static function randomCurrentPreviousPairsWithPositivePrevious(): array
    {
        $faker = Faker::create();
        $data = [];

        for ($i = 0; $i < 100; $i++) {
            $current = $faker->randomFloat(2, 0, 10000);
            $previous = $faker->randomFloat(2, 0.01, 10000);
            $data["iteration_{$i}"] = [$current, $previous];
        }

        return $data;
    }

    /**
     * Generates 100+ random current values paired with zero previous.
     */
    public static function randomCurrentValuesWithZeroPrevious(): array
    {
        $faker = Faker::create();
        $data = [];

        for ($i = 0; $i < 100; $i++) {
            $current = $faker->randomFloat(2, 0, 10000);
            $data["iteration_{$i}"] = [$current];
        }

        // Include explicit edge cases
        $data['zero_current'] = [0];
        $data['large_current'] = [999999];
        $data['small_current'] = [0.01];

        return $data;
    }

    /**
     * Generates 100+ random pairs where current < previous (negative growth).
     */
    public static function randomNegativeGrowthPairs(): array
    {
        $faker = Faker::create();
        $data = [];

        for ($i = 0; $i < 100; $i++) {
            $previous = $faker->randomFloat(2, 1, 10000);
            $current = $faker->randomFloat(2, 0, $previous - 0.01);
            $data["iteration_{$i}"] = [$current, $previous];
        }

        return $data;
    }

    /**
     * Generates 100+ random pairs including zeros for comprehensive coverage.
     */
    public static function allRandomPairs(): array
    {
        $faker = Faker::create();
        $data = [];

        for ($i = 0; $i < 80; $i++) {
            $current = $faker->randomFloat(2, 0, 10000);
            $previous = $faker->randomFloat(2, 0.01, 10000);
            $data["positive_previous_{$i}"] = [$current, $previous];
        }

        for ($i = 0; $i < 20; $i++) {
            $current = $faker->randomFloat(2, 0, 10000);
            $data["zero_previous_{$i}"] = [$current, 0];
        }

        // Edge cases
        $data['both_zero'] = [0, 0];
        $data['same_values'] = [100, 100];
        $data['large_growth'] = [10000, 1];

        return $data;
    }
}
