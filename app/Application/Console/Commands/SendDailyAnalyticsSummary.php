<?php

namespace App\Application\Console\Commands;

use App\Domain\Notification\Services\NotificationService;
use App\Domain\Notification\Support\NotificationMessageResolver;
use App\Domain\Store\Enums\StoreStatus;
use App\Domain\Store\Models\Store;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendDailyAnalyticsSummary extends Command
{
    protected $signature = 'notifications:daily-analytics-summary';

    protected $description = 'Send daily analytics summary notifications to active store owners';

    public function __construct(private readonly NotificationService $notifications)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $today = now()->startOfDay();
        $yesterday = now()->subDay()->startOfDay();

        $stores = Store::query()
            ->where('status', StoreStatus::ACTIVE)
            ->with('owner')
            ->get();

        $sent = 0;

        foreach ($stores as $store) {
            if (! $store->owner) {
                continue;
            }

            try {
                $stats = $this->getStoreStats($store, $yesterday, $today);

                // Only send if there's activity
                if ($stats['views'] === 0 && $stats['claims'] === 0 && $stats['redemptions'] === 0) {
                    continue;
                }

                $resolved = NotificationMessageResolver::resolve('analytics_daily_summary', $stats, $store->owner);

                $this->notifications->send(
                    user: $store->owner,
                    type: 'analytics_daily_summary',
                    title: $resolved['title'],
                    message: $resolved['message'],
                    channel: 'in_app',
                    data: [
                        'store_id' => $store->id,
                        'views' => $stats['views'],
                        'claims' => $stats['claims'],
                        'redemptions' => $stats['redemptions'],
                        'new_followers' => $stats['new_followers'],
                    ],
                    referenceType: Store::class,
                    referenceId: $store->id,
                );

                $sent++;
            } catch (Throwable $e) {
                Log::error('Daily analytics summary notification failed', [
                    'store_id' => $store->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("Sent {$sent} daily analytics summary notifications.");

        return self::SUCCESS;
    }

    private function getStoreStats(Store $store, mixed $from, mixed $to): array
    {
        // Product views for the store's products
        $views = DB::table('product_views')
            ->join('products', 'products.id', '=', 'product_views.product_id')
            ->where('products.store_id', $store->id)
            ->whereBetween('product_views.created_at', [$from, $to])
            ->count();

        // Offer claims
        $claims = DB::table('offer_claims')
            ->where('store_id', $store->id)
            ->whereBetween('created_at', [$from, $to])
            ->count();

        // Redemptions
        $redemptions = DB::table('offer_claims')
            ->where('store_id', $store->id)
            ->whereBetween('redeemed_at', [$from, $to])
            ->whereNotNull('redeemed_at')
            ->count();

        // New followers
        $newFollowers = DB::table('store_followers')
            ->where('store_id', $store->id)
            ->whereBetween('followed_at', [$from, $to])
            ->count();

        return [
            'views' => $views,
            'claims' => $claims,
            'redemptions' => $redemptions,
            'new_followers' => $newFollowers,
        ];
    }
}
