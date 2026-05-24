<?php

namespace App\Domain\Analytics\Services;

class GrowthCalculator
{
    /**
     * Calculate growth percentage between current and previous values.
     *
     * Returns 0.0 when previous is zero (avoids division by zero).
     * Supports negative growth when current < previous.
     * Rounds to 1 decimal place.
     *
     * @param int|float $current The current period value
     * @param int|float $previous The previous period value
     * @return float The growth percentage rounded to 1 decimal place
     */
    public static function calculate(int|float $current, int|float $previous): float
    {
        if ($previous == 0) {
            return 0.0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }
}
