<?php

namespace App\Domain\Analytics\Services;

use Carbon\Carbon;

class PeriodResolver
{
    /**
     * Resolve a period string into current and previous Carbon date ranges.
     *
     * Returns [currentStart, currentEnd, previousStart, previousEnd].
     * For 'all' period, currentStart is null and previous range dates are null.
     */
    public static function resolve(string $period): array
    {
        $now = Carbon::now();

        return match ($period) {
            'today' => self::resolveToday($now),
            'last_7_days' => self::resolveLast7Days($now),
            'this_month' => self::resolveThisMonth($now),
            'this_year' => self::resolveThisYear($now),
            default => self::resolveAll($now),
        };
    }

    private static function resolveAll(Carbon $now): array
    {
        return [
            null,
            $now->copy(),
            null,
            null,
        ];
    }

    private static function resolveToday(Carbon $now): array
    {
        $currentStart = $now->copy()->startOfDay();
        $currentEnd = $now->copy();
        $previousStart = $now->copy()->subDay()->startOfDay();
        $previousEnd = $now->copy()->subDay()->endOfDay();

        return [$currentStart, $currentEnd, $previousStart, $previousEnd];
    }

    private static function resolveLast7Days(Carbon $now): array
    {
        $currentStart = $now->copy()->subDays(7);
        $currentEnd = $now->copy();
        $previousStart = $now->copy()->subDays(14);
        $previousEnd = $now->copy()->subDays(7);

        return [$currentStart, $currentEnd, $previousStart, $previousEnd];
    }

    private static function resolveThisMonth(Carbon $now): array
    {
        $currentStart = $now->copy()->startOfMonth();
        $currentEnd = $now->copy();
        $previousStart = $now->copy()->subMonth()->startOfMonth();
        $previousEnd = $now->copy()->subMonth()->endOfMonth();

        return [$currentStart, $currentEnd, $previousStart, $previousEnd];
    }

    private static function resolveThisYear(Carbon $now): array
    {
        $currentStart = $now->copy()->startOfYear();
        $currentEnd = $now->copy();
        $previousStart = $now->copy()->subYear()->startOfYear();
        $previousEnd = $now->copy()->subYear()->endOfYear();

        return [$currentStart, $currentEnd, $previousStart, $previousEnd];
    }
}
