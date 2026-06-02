<?php

namespace Tests\Unit\Domain\Explore;

use App\Domain\Explore\Support\HaversineCalculator;
use Faker\Factory as Faker;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

class HaversineCalculatorTest extends TestCase
{
    /** @test */
    public function it_returns_zero_for_identical_points(): void
    {
        $distance = HaversineCalculator::distanceKm(30.0444, 31.2357, 30.0444, 31.2357);

        $this->assertEquals(0.0, $distance);
    }

    /** @test */
    public function it_calculates_cairo_to_alexandria_distance(): void
    {
        // Cairo: 30.0444°N, 31.2357°E
        // Alexandria: 31.2001°N, 29.9187°E
        $distance = HaversineCalculator::distanceKm(30.0444, 31.2357, 31.2001, 29.9187);

        // Known distance is approximately 180 km
        $this->assertEqualsWithDelta(180.0, $distance, 10.0);
    }

    /** @test */
    public function it_returns_non_negative_distance(): void
    {
        $distance = HaversineCalculator::distanceKm(40.7128, -74.0060, 51.5074, -0.1278);

        $this->assertGreaterThanOrEqual(0.0, $distance);
    }

    /** @test */
    public function it_is_symmetric(): void
    {
        $distanceAB = HaversineCalculator::distanceKm(30.0444, 31.2357, 31.2001, 29.9187);
        $distanceBA = HaversineCalculator::distanceKm(31.2001, 29.9187, 30.0444, 31.2357);

        $this->assertEqualsWithDelta($distanceAB, $distanceBA, 0.0001);
    }

    /** @test */
    public function it_satisfies_triangle_inequality(): void
    {
        // Cairo, Alexandria, Luxor
        $distAB = HaversineCalculator::distanceKm(30.0444, 31.2357, 31.2001, 29.9187);
        $distBC = HaversineCalculator::distanceKm(31.2001, 29.9187, 25.6872, 32.6396);
        $distAC = HaversineCalculator::distanceKm(30.0444, 31.2357, 25.6872, 32.6396);

        $this->assertGreaterThanOrEqual($distAC, $distAB + $distBC);
    }

    /** @test */
    public function it_calculates_new_york_to_london_distance(): void
    {
        // New York: 40.7128°N, 74.0060°W
        // London: 51.5074°N, 0.1278°W
        $distance = HaversineCalculator::distanceKm(40.7128, -74.0060, 51.5074, -0.1278);

        // Known distance is approximately 5570 km
        $this->assertEqualsWithDelta(5570.0, $distance, 50.0);
    }

    /** @test */
    public function it_handles_equator_crossing(): void
    {
        // Point on equator
        $distance = HaversineCalculator::distanceKm(0.0, 0.0, 0.0, 1.0);

        // 1 degree of longitude at equator ≈ 111.32 km
        $this->assertEqualsWithDelta(111.32, $distance, 1.0);
    }

    /** @test */
    public function it_handles_antipodal_points(): void
    {
        // Opposite sides of the Earth
        $distance = HaversineCalculator::distanceKm(0.0, 0.0, 0.0, 180.0);

        // Half the Earth's circumference ≈ 20015 km
        $this->assertEqualsWithDelta(20015.0, $distance, 100.0);
    }

    /**
     * Property 8: Haversine Distance Calculation - Non-negative results
     * Validates: Requirements 5.3
     *
     * For any two valid coordinate pairs, HaversineCalculator::distanceKm()
     * SHALL return a non-negative value.
     */
    #[DataProvider('randomCoordinatePairsProvider')]
    #[Group('explore-page')]
    public function test_property_distance_is_always_non_negative(
        float $lat1,
        float $lng1,
        float $lat2,
        float $lng2
    ): void {
        $distance = HaversineCalculator::distanceKm($lat1, $lng1, $lat2, $lng2);

        $this->assertGreaterThanOrEqual(
            0.0,
            $distance,
            "Distance should be non-negative for ($lat1, $lng1) -> ($lat2, $lng2)"
        );
    }

    /**
     * Property 8: Haversine Distance Calculation - Zero for identical points
     * Validates: Requirements 5.3
     *
     * For any valid coordinate pair, HaversineCalculator::distanceKm()
     * SHALL return 0 when both points are identical.
     */
    #[DataProvider('randomSingleCoordinatesProvider')]
    #[Group('explore-page')]
    public function test_property_distance_is_zero_for_identical_points(
        float $lat,
        float $lng
    ): void {
        $distance = HaversineCalculator::distanceKm($lat, $lng, $lat, $lng);

        $this->assertEqualsWithDelta(
            0.0,
            $distance,
            0.0001,
            "Distance should be zero for identical points ($lat, $lng)"
        );
    }

    /**
     * Property 8: Haversine Distance Calculation - Triangle inequality
     * Validates: Requirements 5.3
     *
     * For any three valid coordinate pairs A, B, C:
     * distance(A,B) + distance(B,C) >= distance(A,C)
     */
    #[DataProvider('randomCoordinateTripletsProvider')]
    #[Group('explore-page')]
    public function test_property_triangle_inequality_holds(
        float $latA,
        float $lngA,
        float $latB,
        float $lngB,
        float $latC,
        float $lngC
    ): void {
        $distAB = HaversineCalculator::distanceKm($latA, $lngA, $latB, $lngB);
        $distBC = HaversineCalculator::distanceKm($latB, $lngB, $latC, $lngC);
        $distAC = HaversineCalculator::distanceKm($latA, $lngA, $latC, $lngC);

        $this->assertGreaterThanOrEqual(
            $distAC - 0.0001,
            $distAB + $distBC,
            "Triangle inequality violated: dist(A,B) + dist(B,C) < dist(A,C) for A=($latA,$lngA), B=($latB,$lngB), C=($latC,$lngC)"
        );
    }

    /**
     * @return array<string, array{float, float, float, float}>
     */
    public static function randomCoordinatePairsProvider(): array
    {
        $faker = Faker::create();
        $datasets = [];

        for ($i = 0; $i < 100; $i++) {
            $datasets["pair_$i"] = [
                $faker->randomFloat(6, -90, 90),
                $faker->randomFloat(6, -180, 180),
                $faker->randomFloat(6, -90, 90),
                $faker->randomFloat(6, -180, 180),
            ];
        }

        return $datasets;
    }

    /**
     * @return array<string, array{float, float}>
     */
    public static function randomSingleCoordinatesProvider(): array
    {
        $faker = Faker::create();
        $datasets = [];

        for ($i = 0; $i < 100; $i++) {
            $datasets["point_$i"] = [
                $faker->randomFloat(6, -90, 90),
                $faker->randomFloat(6, -180, 180),
            ];
        }

        return $datasets;
    }

    /**
     * @return array<string, array{float, float, float, float, float, float}>
     */
    public static function randomCoordinateTripletsProvider(): array
    {
        $faker = Faker::create();
        $datasets = [];

        for ($i = 0; $i < 100; $i++) {
            $datasets["triplet_$i"] = [
                $faker->randomFloat(6, -90, 90),
                $faker->randomFloat(6, -180, 180),
                $faker->randomFloat(6, -90, 90),
                $faker->randomFloat(6, -180, 180),
                $faker->randomFloat(6, -90, 90),
                $faker->randomFloat(6, -180, 180),
            ];
        }

        return $datasets;
    }
}
