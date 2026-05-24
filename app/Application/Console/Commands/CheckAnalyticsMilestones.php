<?php

namespace App\Application\Console\Commands;

use App\Domain\Notification\Models\Notification;
use App\Domain\Notification\Services\NotificationService;
use App\Domain\Notification\Support\NotificationMessageResolver;
use App\Domain\Store\Enums\StoreStatus;
use App\Domain\Store\Models\Store;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class CheckAnalyticsMilestones extends Command
{
    protected $signature = 'notifications:check-milestones';

    protected $description = 'Check and send milestone notifications for stores';

    private const MILESTONES = [10, 25, 50, 100, 250, 500, 1000, 2500, 5000, 10000];

    public function __construct(private readonly NotificationService $notifications)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $stores = Store::query()
            ->where('status', StoreStatus::ACTIVE)
            ->with('owner')
            ->get();

        $sent = 0;

        foreach ($stores as $store) {
            if (! $store->owner) {
                continue;
            }

            $sent += $this->checkMilestone($store, 'followers', $store->followers_count ?? 0);
            $sent += $this->checkMilestone($store, 'redemptions', $this->getRedemptionCount($store));
            $sent += $this->checkMilestone($store, 'claims', $this->getClaimCount($store));
            $sent += $this->checkMilestone($store, 'views', $this->getViewCount($store));
        }

        $this->info("Sent {$sent} milestone notifications.");

        return self::SUCCESS;
    }

    private function checkMilestone(Store $store, string $milestoneType, int $currentValue): int
    {
        $sent = 0;

        foreach (self::MILESTONES as $milestone) {
            if ($currentValue < $milestone) {
                break;
            }

            // Check if we already sent this milestone notification
            $alreadySent = Notification::query()
                ->where('user_id', $store->owner->id)
                ->where('type', 'analytics_milestone')
                ->where('data->store_id', $store->id)
                ->where('data->milestone_type', $milestoneType)
                ->where('data->milestone_value', $milestone)
                ->exists();

            if ($alreadySent) {
                continue;
            }

            $resolved = NotificationMessageResolver::resolve('analytics_milestone', [
                'milestone_value' => $milestone,
                'milestone_type' => $milestoneType,
            ], $store->owner);

            try {
                $this->notifications->send(
                    user: $store->owner,
                    type: 'analytics_milestone',
                    title: $resolved['title'],
                    message: $resolved['message'],
                    channel: 'in_app',
                    data: [
                        'store_id' => $store->id,
                        'milestone_type' => $milestoneType,
                        'milestone_value' => $milestone,
                    ],
                    referenceType: Store::class,
                    referenceId: $store->id,
                );

                $sent++;
            } catch (Throwable $e) {
                Log::error('Milestone notification failed', [
                    'store_id' => $store->id,
                    'milestone_type' => $milestoneType,
                    'milestone_value' => $milestone,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $sent;
    }

    private function getRedemptionCount(Store $store): int
    {
        return (int) DB::table('offer_claims')
            ->where('store_id', $store->id)
            ->whereNotNull('redeemed_at')
            ->count();
    }

    private function getClaimCount(Store $store): int
    {
        return (int) DB::table('offer_claims')
            ->where('store_id', $store->id)
            ->count();
    }

    private function getViewCount(Store $store): int
    {
        return (int) DB::table('product_views')
            ->join('products', 'products.id', '=', 'product_views.product_id')
            ->where('products.store_id', $store->id)
            ->count();
    }
}
