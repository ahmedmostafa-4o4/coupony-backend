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
    public static function resolve(string $period, ?string $startDate = null, ?string $endDate = null): array
    {
        $now = Carbon::now();

        if ($startDate !== null && $endDate !== null) {
            return self::resolveCustom($startDate, $endDate);
        }

        return match ($period) {
            'today' => self::resolveToday($now),
            'last_7_days' => self::resolveLast7Days($now),
            'last_30_days' => self::resolveLast30Days($now),
            'this_month' => self::resolveThisMonth($now),
            'this_year' => self::resolveThisYear($now),
            default => self::resolveAll($now),
        };
    }

    public static function cacheKey(string $period, ?string $startDate = null, ?string $endDate = null): string
    {
        return $startDate !== null && $endDate !== null
            ? "custom:{$startDate}:{$endDate}"
            : $period;
    }

    private static function resolveCustom(string $startDate, string $endDate): array
    {
        $currentStart = Carbon::createFromFormat('Y-m-d', $startDate)->startOfDay();
        $currentEnd = Carbon::createFromFormat('Y-m-d', $endDate)->endOfDay();
        $duration = $currentStart->diffInSeconds($currentEnd) + 1;
        $previousEnd = $currentStart->copy()->subSecond();
        $previousStart = $previousEnd->copy()->subSeconds($duration - 1);

        return [$currentStart, $currentEnd, $previousStart, $previousEnd];
    }

    private static function resolveLast30Days(Carbon $now): array
    {
        return [
            $now->copy()->subDays(30),
            $now->copy(),
            $now->copy()->subDays(60),
            $now->copy()->subDays(30),
        ];
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
