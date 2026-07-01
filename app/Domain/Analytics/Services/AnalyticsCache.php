<?php

namespace App\Domain\Analytics\Services;

use Illuminate\Support\Facades\Cache;

class AnalyticsCache
{
    public static function sellerKey(string $storeId, string $rangeKey): string
    {
        return "seller_analytics:{$storeId}:v".self::version("seller:{$storeId}").":{$rangeKey}";
    }

    public static function productKey(string $productId, string $rangeKey): string
    {
        return "product_analytics:{$productId}:v".self::version("product:{$productId}").":{$rangeKey}";
    }

    public static function invalidateSeller(string $storeId): void
    {
        self::incrementVersion("seller:{$storeId}");
    }

    public static function invalidateProduct(string $productId): void
    {
        self::incrementVersion("product:{$productId}");
    }

    private static function version(string $subject): int
    {
        return (int) Cache::get("analytics_version:{$subject}", 1);
    }

    private static function incrementVersion(string $subject): void
    {
        Cache::forever("analytics_version:{$subject}", self::version($subject) + 1);
    }
}
