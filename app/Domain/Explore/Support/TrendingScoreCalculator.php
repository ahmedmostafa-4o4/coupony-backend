<?php

namespace App\Domain\Explore\Support;

class TrendingScoreCalculator
{
    /**
     * Calculate trending score using the formula:
     * active_campaign_priority * 3 + saved_count * 1 + views_last_7_days * 0.5
     * + discount_percent * 0.2 + recency_score
     *
     * recency_score: days since creation, capped at 30, inverted (30 - days)
     */
    public static function calculate(
        int $campaignPriority,
        int $savedCount,
        int $viewsLast7Days,
        float $discountPercent,
        float $recencyScore
    ): float {
        return ($campaignPriority * 3)
            + ($savedCount * 1)
            + ($viewsLast7Days * 0.5)
            + ($discountPercent * 0.2)
            + $recencyScore;
    }
}
