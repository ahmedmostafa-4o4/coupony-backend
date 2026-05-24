<?php

namespace App\Domain\Analytics\Services;

use Illuminate\Support\Collection;

class HeatmapBuilder
{
    /**
     * The 7 days of the week (Monday through Sunday).
     */
    private const DAYS = [
        'monday',
        'tuesday',
        'wednesday',
        'thursday',
        'friday',
        'saturday',
        'sunday',
    ];

    /**
     * The 4 time windows within each day.
     */
    private const TIME_WINDOWS = [
        'morning',
        'afternoon',
        'evening',
        'night',
    ];

    /**
     * Build a 28-bucket heatmap from redemption timestamps.
     *
     * Always returns exactly 28 buckets (7 days × 4 time windows).
     * Each bucket contains: day, time_window, count.
     * Missing slots have count = 0.
     *
     * @param Collection $redemptions Collection of redemption records with redeemed_at timestamps
     * @return array Array of 28 heatmap bucket objects
     */
    public static function build(Collection $redemptions): array
    {
        // Initialize all 28 buckets with count = 0
        $buckets = [];
        foreach (self::DAYS as $day) {
            foreach (self::TIME_WINDOWS as $timeWindow) {
                $buckets[$day . '_' . $timeWindow] = [
                    'day' => $day,
                    'time_window' => $timeWindow,
                    'count' => 0,
                ];
            }
        }

        // Map each redemption to its bucket and increment count
        foreach ($redemptions as $redemption) {
            $timestamp = $redemption->redeemed_at;

            if ($timestamp === null) {
                continue;
            }

            $carbon = \Carbon\Carbon::parse($timestamp);
            $day = strtolower($carbon->format('l'));
            $hour = (int) $carbon->format('H');
            $timeWindow = self::resolveTimeWindow($hour);

            $key = $day . '_' . $timeWindow;
            if (isset($buckets[$key])) {
                $buckets[$key]['count']++;
            }
        }

        return array_values($buckets);
    }

    /**
     * Resolve the time window for a given hour.
     *
     * morning: 06:00–11:59
     * afternoon: 12:00–17:59
     * evening: 18:00–23:59
     * night: 00:00–05:59
     *
     * @param int $hour The hour (0-23)
     * @return string The time window name
     */
    private static function resolveTimeWindow(int $hour): string
    {
        if ($hour >= 6 && $hour <= 11) {
            return 'morning';
        }

        if ($hour >= 12 && $hour <= 17) {
            return 'afternoon';
        }

        if ($hour >= 18 && $hour <= 23) {
            return 'evening';
        }

        return 'night';
    }
}
