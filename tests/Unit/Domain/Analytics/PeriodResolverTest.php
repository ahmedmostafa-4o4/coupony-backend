<?php

namespace Tests\Unit\Domain\Analytics;

use App\Domain\Analytics\Services\PeriodResolver;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for PeriodResolver service.
 *
 * Validates: Requirements 1.1, 1.2
 */
class PeriodResolverTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Freeze time for deterministic tests
        Carbon::setTestNow(Carbon::create(2025, 6, 15, 14, 30, 0));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow(null);

        parent::tearDown();
    }

    /**
     * Test 'all' period: currentStart is null, currentEnd is now, previousStart is null, previousEnd is null.
     * Validates: Requirements 1.1, 1.2
     */
    public function test_all_period_returns_null_current_start_and_null_previous_range(): void
    {
        [$currentStart, $currentEnd, $previousStart, $previousEnd] = PeriodResolver::resolve('all');

        $this->assertNull($currentStart);
        $this->assertInstanceOf(Carbon::class, $currentEnd);
        $this->assertTrue($currentEnd->equalTo(Carbon::now()));
        $this->assertNull($previousStart);
        $this->assertNull($previousEnd);
    }

    /**
     * Test 'today' period: currentStart is today start, currentEnd is now,
     * previousStart is yesterday start, previousEnd is yesterday end.
     * Validates: Requirements 1.1, 1.2
     */
    public function test_today_period_returns_correct_date_ranges(): void
    {
        [$currentStart, $currentEnd, $previousStart, $previousEnd] = PeriodResolver::resolve('today');

        // Current range: today 00:00 → now
        $this->assertTrue($currentStart->equalTo(Carbon::today()->startOfDay()));
        $this->assertTrue($currentEnd->equalTo(Carbon::now()));

        // Previous range: yesterday 00:00 → yesterday 23:59:59
        $this->assertTrue($previousStart->equalTo(Carbon::yesterday()->startOfDay()));
        $this->assertTrue($previousEnd->equalTo(Carbon::yesterday()->endOfDay()));
    }

    /**
     * Test 'last_7_days' period: correct 7-day and 14-day ranges.
     * Validates: Requirements 1.1, 1.2
     */
    public function test_last_7_days_period_returns_correct_date_ranges(): void
    {
        [$currentStart, $currentEnd, $previousStart, $previousEnd] = PeriodResolver::resolve('last_7_days');

        $now = Carbon::now();

        // Current range: now - 7 days → now
        $this->assertTrue($currentStart->equalTo($now->copy()->subDays(7)));
        $this->assertTrue($currentEnd->equalTo($now));

        // Previous range: now - 14 days → now - 7 days
        $this->assertTrue($previousStart->equalTo($now->copy()->subDays(14)));
        $this->assertTrue($previousEnd->equalTo($now->copy()->subDays(7)));
    }

    /**
     * Test 'this_month' period: correct month boundaries.
     * Validates: Requirements 1.1, 1.2
     */
    public function test_this_month_period_returns_correct_date_ranges(): void
    {
        [$currentStart, $currentEnd, $previousStart, $previousEnd] = PeriodResolver::resolve('this_month');

        $now = Carbon::now();

        // Current range: month start → now
        $this->assertTrue($currentStart->equalTo($now->copy()->startOfMonth()));
        $this->assertTrue($currentEnd->equalTo($now));

        // Previous range: previous month start → previous month end
        $this->assertTrue($previousStart->equalTo($now->copy()->subMonth()->startOfMonth()));
        $this->assertTrue($previousEnd->equalTo($now->copy()->subMonth()->endOfMonth()));
    }

    /**
     * Test 'this_year' period: correct year boundaries.
     * Validates: Requirements 1.1, 1.2
     */
    public function test_this_year_period_returns_correct_date_ranges(): void
    {
        [$currentStart, $currentEnd, $previousStart, $previousEnd] = PeriodResolver::resolve('this_year');

        $now = Carbon::now();

        // Current range: year start → now
        $this->assertTrue($currentStart->equalTo($now->copy()->startOfYear()));
        $this->assertTrue($currentEnd->equalTo($now));

        // Previous range: previous year start → previous year end
        $this->assertTrue($previousStart->equalTo($now->copy()->subYear()->startOfYear()));
        $this->assertTrue($previousEnd->equalTo($now->copy()->subYear()->endOfYear()));
    }

    /**
     * Test unknown/default period falls back to 'all' behavior.
     * Validates: Requirements 1.2
     */
    public function test_unknown_period_falls_back_to_all_behavior(): void
    {
        [$currentStart, $currentEnd, $previousStart, $previousEnd] = PeriodResolver::resolve('invalid_period');

        $this->assertNull($currentStart);
        $this->assertInstanceOf(Carbon::class, $currentEnd);
        $this->assertTrue($currentEnd->equalTo(Carbon::now()));
        $this->assertNull($previousStart);
        $this->assertNull($previousEnd);
    }

    /**
     * Test that all resolved values are Carbon instances (except nulls).
     * Validates: Requirements 1.1
     */
    public function test_resolved_dates_are_carbon_instances(): void
    {
        $periods = ['today', 'last_7_days', 'this_month', 'this_year'];

        foreach ($periods as $period) {
            [$currentStart, $currentEnd, $previousStart, $previousEnd] = PeriodResolver::resolve($period);

            $this->assertInstanceOf(Carbon::class, $currentStart, "currentStart for '{$period}' should be Carbon");
            $this->assertInstanceOf(Carbon::class, $currentEnd, "currentEnd for '{$period}' should be Carbon");
            $this->assertInstanceOf(Carbon::class, $previousStart, "previousStart for '{$period}' should be Carbon");
            $this->assertInstanceOf(Carbon::class, $previousEnd, "previousEnd for '{$period}' should be Carbon");
        }
    }

    /**
     * Test that currentEnd is always equal to now for all periods.
     * Validates: Requirements 1.1
     */
    public function test_current_end_is_always_now(): void
    {
        $periods = ['all', 'today', 'last_7_days', 'this_month', 'this_year'];

        foreach ($periods as $period) {
            [, $currentEnd, ,] = PeriodResolver::resolve($period);

            $this->assertTrue(
                $currentEnd->equalTo(Carbon::now()),
                "currentEnd for '{$period}' should equal now"
            );
        }
    }

    /**
     * Test that previous range always ends before or at current range start for non-all periods.
     * Validates: Requirements 1.1
     */
    public function test_previous_range_does_not_overlap_current_range(): void
    {
        $periods = ['today', 'last_7_days', 'this_month', 'this_year'];

        foreach ($periods as $period) {
            [$currentStart, , , $previousEnd] = PeriodResolver::resolve($period);

            $this->assertTrue(
                $previousEnd->lessThanOrEqualTo($currentStart),
                "previousEnd should be <= currentStart for '{$period}'"
            );
        }
    }
}
