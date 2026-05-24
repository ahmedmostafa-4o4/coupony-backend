<?php

namespace Tests\Unit\Domain\Analytics;

use App\Domain\Analytics\Services\HeatmapBuilder;
use Carbon\Carbon;
use Faker\Factory as Faker;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Property 3: Heatmap Structure Invariant
 *
 * For any collection of redemption timestamps (including empty collections),
 * the HeatmapBuilder::build() method SHALL return an array of exactly 28 objects,
 * each containing a valid day (one of 7 weekdays), a valid time_window
 * (one of: morning, afternoon, evening, night), and a non-negative integer count.
 *
 * **Validates: Requirements 6.1, 6.2, 6.3**
 */
class HeatmapBuilderTest extends TestCase
{
    private const VALID_DAYS = [
        'monday',
        'tuesday',
        'wednesday',
        'thursday',
        'friday',
        'saturday',
        'sunday',
    ];

    private const VALID_TIME_WINDOWS = [
        'morning',
        'afternoon',
        'evening',
        'night',
    ];

    /**
     * @return array<int, array{Collection}>
     */
    public static function randomRedemptionCollectionsProvider(): array
    {
        $faker = Faker::create();
        $datasets = [];

        for ($i = 0; $i < 100; $i++) {
            $count = $faker->numberBetween(0, 500);
            $items = [];

            for ($j = 0; $j < $count; $j++) {
                $item = new \stdClass();
                $item->redeemed_at = Carbon::create(
                    $faker->numberBetween(2020, 2025),
                    $faker->numberBetween(1, 12),
                    $faker->numberBetween(1, 28),
                    $faker->numberBetween(0, 23),
                    $faker->numberBetween(0, 59),
                    $faker->numberBetween(0, 59)
                );
                $items[] = $item;
            }

            $datasets[] = [new Collection($items)];
        }

        return $datasets;
    }

    #[DataProvider('randomRedemptionCollectionsProvider')]
    public function test_heatmap_always_returns_exactly_28_elements(Collection $redemptions): void
    {
        $result = HeatmapBuilder::build($redemptions);

        $this->assertCount(28, $result);
    }

    #[DataProvider('randomRedemptionCollectionsProvider')]
    public function test_each_element_has_required_keys(Collection $redemptions): void
    {
        $result = HeatmapBuilder::build($redemptions);

        foreach ($result as $index => $bucket) {
            $this->assertArrayHasKey('day', $bucket, "Bucket at index {$index} missing 'day' key");
            $this->assertArrayHasKey('time_window', $bucket, "Bucket at index {$index} missing 'time_window' key");
            $this->assertArrayHasKey('count', $bucket, "Bucket at index {$index} missing 'count' key");
        }
    }

    #[DataProvider('randomRedemptionCollectionsProvider')]
    public function test_each_day_is_a_valid_weekday(Collection $redemptions): void
    {
        $result = HeatmapBuilder::build($redemptions);

        foreach ($result as $index => $bucket) {
            $this->assertContains(
                $bucket['day'],
                self::VALID_DAYS,
                "Bucket at index {$index} has invalid day: '{$bucket['day']}'"
            );
        }
    }

    #[DataProvider('randomRedemptionCollectionsProvider')]
    public function test_each_time_window_is_valid(Collection $redemptions): void
    {
        $result = HeatmapBuilder::build($redemptions);

        foreach ($result as $index => $bucket) {
            $this->assertContains(
                $bucket['time_window'],
                self::VALID_TIME_WINDOWS,
                "Bucket at index {$index} has invalid time_window: '{$bucket['time_window']}'"
            );
        }
    }

    #[DataProvider('randomRedemptionCollectionsProvider')]
    public function test_each_count_is_a_non_negative_integer(Collection $redemptions): void
    {
        $result = HeatmapBuilder::build($redemptions);

        foreach ($result as $index => $bucket) {
            $this->assertIsInt($bucket['count'], "Bucket at index {$index} count is not an integer");
            $this->assertGreaterThanOrEqual(
                0,
                $bucket['count'],
                "Bucket at index {$index} has negative count: {$bucket['count']}"
            );
        }
    }

    #[DataProvider('randomRedemptionCollectionsProvider')]
    public function test_sum_of_counts_equals_number_of_input_redemptions(Collection $redemptions): void
    {
        $result = HeatmapBuilder::build($redemptions);

        $totalCount = array_sum(array_column($result, 'count'));

        $this->assertSame(
            $redemptions->count(),
            $totalCount,
            "Sum of bucket counts ({$totalCount}) does not equal input redemption count ({$redemptions->count()})"
        );
    }
}
