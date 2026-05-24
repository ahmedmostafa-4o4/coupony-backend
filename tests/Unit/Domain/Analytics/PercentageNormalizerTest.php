<?php

namespace Tests\Unit\Domain\Analytics;

use App\Domain\Analytics\Services\PercentageNormalizer;
use Faker\Factory as Faker;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PercentageNormalizerTest extends TestCase
{
    public function test_returns_empty_array_for_empty_input(): void
    {
        $result = PercentageNormalizer::normalize([]);

        $this->assertSame([], $result);
    }

    public function test_returns_empty_array_for_all_zeros(): void
    {
        $result = PercentageNormalizer::normalize([0, 0, 0]);

        $this->assertSame([], $result);
    }

    public function test_single_value_returns_100(): void
    {
        $result = PercentageNormalizer::normalize([50]);

        $this->assertSame([100.0], $result);
    }

    public function test_equal_values_sum_to_100(): void
    {
        $result = PercentageNormalizer::normalize([10, 10, 10]);

        $this->assertCount(3, $result);
        $this->assertEqualsWithDelta(100.0, array_sum($result), 0.001);
    }

    public function test_values_are_rounded_to_one_decimal(): void
    {
        $result = PercentageNormalizer::normalize([1, 2, 3]);

        foreach ($result as $value) {
            // Check that each value has at most 1 decimal place
            $this->assertSame(round($value, 1), $value);
        }
    }

    public function test_sum_equals_exactly_100_with_rounding_drift(): void
    {
        // 3 equal values: 100/3 = 33.333... rounds to 33.3 each = 99.9
        // The largest should be adjusted to 33.4 to make sum = 100.0
        $result = PercentageNormalizer::normalize([1, 1, 1]);

        $this->assertEqualsWithDelta(100.0, array_sum($result), 0.001);
    }

    public function test_output_array_has_same_length_as_input(): void
    {
        $input = [5, 10, 15, 20, 50];
        $result = PercentageNormalizer::normalize($input);

        $this->assertCount(count($input), $result);
    }

    public function test_known_distribution(): void
    {
        // 50 + 30 + 20 = 100, so percentages should be 50.0, 30.0, 20.0
        $result = PercentageNormalizer::normalize([50, 30, 20]);

        $this->assertSame([50.0, 30.0, 20.0], $result);
    }

    public function test_preserves_index_order(): void
    {
        $result = PercentageNormalizer::normalize([1, 99]);

        // 1/100 = 1.0%, 99/100 = 99.0%
        $this->assertSame([1.0, 99.0], $result);
    }

    /**
     * Property 2: Percentage Array Normalization
     * Validates: Requirements 5.3, 9.3, 11.2, 11.5, 11.6, 15.1, 15.2, 15.3
     *
     * For any non-empty array of positive numeric values, the normalize() method SHALL produce
     * an output array where the sum equals exactly 100.0, each value is rounded to 1 decimal
     * place, and the output array has the same length as the input array.
     */
    #[DataProvider('randomPositiveFloatArraysProvider')]
    public function test_property_normalized_output_sums_to_100(array $input): void
    {
        $result = PercentageNormalizer::normalize($input);

        $this->assertEqualsWithDelta(100.0, array_sum($result), 0.001, sprintf(
            'Sum of normalized values should be 100.0, got %s for input: [%s]',
            array_sum($result),
            implode(', ', $input)
        ));
    }

    #[DataProvider('randomPositiveFloatArraysProvider')]
    public function test_property_each_value_rounded_to_one_decimal(array $input): void
    {
        $result = PercentageNormalizer::normalize($input);

        foreach ($result as $index => $value) {
            $this->assertSame(
                round($value, 1),
                $value,
                sprintf(
                    'Value at index %d should be rounded to 1 decimal place, got %s for input: [%s]',
                    $index,
                    $value,
                    implode(', ', $input)
                )
            );
        }
    }

    #[DataProvider('randomPositiveFloatArraysProvider')]
    public function test_property_output_length_matches_input_length(array $input): void
    {
        $result = PercentageNormalizer::normalize($input);

        $this->assertCount(
            \count($input),
            $result,
            sprintf(
                'Output array length should match input length of %d, got %d for input: [%s]',
                \count($input),
                \count($result),
                implode(', ', $input)
            )
        );
    }

    /**
     * Data provider generating 100 random arrays of 1-10 positive floats using Faker.
     *
     * @return array<string, array{array<float>}>
     */
    public static function randomPositiveFloatArraysProvider(): array
    {
        $faker = Faker::create();
        $testCases = [];

        for ($i = 0; $i < 100; $i++) {
            $length = $faker->numberBetween(1, 10);
            $values = [];

            for ($j = 0; $j < $length; $j++) {
                $values[] = $faker->randomFloat(2, 0.01, 1000.0);
            }

            $testCases["random_array_iteration_{$i}"] = [$values];
        }

        return $testCases;
    }
}
