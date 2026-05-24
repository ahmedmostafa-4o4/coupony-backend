<?php

namespace App\Domain\Analytics\Services;

class PercentageNormalizer
{
    /**
     * Normalize an array of raw counts into percentages summing to exactly 100.0.
     * Each value is rounded to 1 decimal place.
     * The largest value is adjusted to compensate for rounding drift.
     * Returns empty array if input is empty or all zeros.
     *
     * @param  array<int|float>  $values
     * @return array<float>
     */
    public static function normalize(array $values): array
    {
        if (empty($values)) {
            return [];
        }

        $total = array_sum($values);

        if ($total == 0) {
            return [];
        }

        // Calculate raw percentages and round to 1 decimal place
        $percentages = array_map(
            fn ($value) => round(($value / $total) * 100, 1),
            $values
        );

        // Calculate the rounding drift
        $sum = round(array_sum($percentages), 1);
        $drift = round(100.0 - $sum, 1);

        if ($drift != 0.0) {
            // Find the index of the largest value and adjust it
            $maxIndex = 0;
            $maxValue = $percentages[0];

            foreach ($percentages as $index => $percentage) {
                if ($percentage > $maxValue) {
                    $maxValue = $percentage;
                    $maxIndex = $index;
                }
            }

            $percentages[$maxIndex] = round($percentages[$maxIndex] + $drift, 1);
        }

        return $percentages;
    }
}
