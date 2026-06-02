<?php

namespace Tests\Unit\Domain\Explore;

use App\Domain\Explore\Support\TrendingScoreCalculator;
use Faker\Factory as Faker;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

class TrendingScoreCalculatorTest extends TestCase
{
    public function test_calculate_with_known_values(): void
    {
        // campaign_priority=2 * 3 = 6
        // saved_count=10 * 1 = 10
        // views_last_7_days=100 * 0.5 = 50
        // discount_percent=25.0 * 0.2 = 5
        // recency_score=20.0
        // Total = 6 + 10 + 50 + 5 + 20 = 91.0
        $result = TrendingScoreCalculator::calculate(2, 10, 100, 25.0, 20.0);

        $this->assertSame(91.0, $result);
    }

    public function test_calculate_with_all_zeros(): void
    {
        $result = TrendingScoreCalculator::calculate(0, 0, 0, 0.0, 0.0);

        $this->assertSame(0.0, $result);
    }

    public function test_calculate_returns_float(): void
    {
        $result = TrendingScoreCalculator::calculate(1, 1, 1, 1.0, 1.0);

        $this->assertIsFloat($result);
    }

    public function test_calculate_with_high_campaign_priority(): void
    {
        // campaign_priority=10 * 3 = 30, rest zero
        $result = TrendingScoreCalculator::calculate(10, 0, 0, 0.0, 0.0);

        $this->assertSame(30.0, $result);
    }

    public function test_calculate_with_max_recency_score(): void
    {
        // recency_score max is 30 (brand new product: 30 - 0 days = 30)
        $result = TrendingScoreCalculator::calculate(0, 0, 0, 0.0, 30.0);

        $this->assertSame(30.0, $result);
    }

    /**
     * Property 4: Trending Score Calculation
     * Validates: Requirements 2.2
     *
     * For any set of non-negative input values, TrendingScoreCalculator::calculate()
     * SHALL return campaign_priority * 3 + saved_count * 1 + views_last_7_days * 0.5
     * + discount_percent * 0.2 + recency_score.
     */
    #[DataProvider('trendingScoreRandomTuplesProvider')]
    #[Group('explore-page')]
    public function test_property_trending_score_calculation(
        int $campaignPriority,
        int $savedCount,
        int $viewsLast7Days,
        float $discountPercent,
        float $recencyScore
    ): void {
        $expected = $campaignPriority * 3
            + $savedCount * 1
            + $viewsLast7Days * 0.5
            + $discountPercent * 0.2
            + $recencyScore;

        $result = TrendingScoreCalculator::calculate(
            $campaignPriority,
            $savedCount,
            $viewsLast7Days,
            $discountPercent,
            $recencyScore
        );

        $this->assertEqualsWithDelta(
            $expected,
            $result,
            0.0001,
            "Failed for inputs: priority=$campaignPriority, saved=$savedCount, views=$viewsLast7Days, discount=$discountPercent, recency=$recencyScore"
        );
    }

    /**
     * @return array<string, array{int, int, int, float, float}>
     */
    public static function trendingScoreRandomTuplesProvider(): array
    {
        $faker = Faker::create();
        $datasets = [];

        for ($i = 0; $i < 100; $i++) {
            $campaignPriority = $faker->numberBetween(0, 10);
            $savedCount = $faker->numberBetween(0, 10000);
            $viewsLast7Days = $faker->numberBetween(0, 50000);
            $discountPercent = $faker->randomFloat(2, 0, 100);
            $recencyScore = $faker->randomFloat(2, 0, 30);

            $datasets["tuple_$i"] = [
                $campaignPriority,
                $savedCount,
                $viewsLast7Days,
                $discountPercent,
                $recencyScore,
            ];
        }

        return $datasets;
    }
}
