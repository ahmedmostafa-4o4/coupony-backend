<?php

namespace App\Domain\Subscription\Jobs;

use App\Domain\Subscription\Actions\TransitionSubscriptionAction;
use App\Domain\Subscription\Enums\SubscriptionStatus;
use App\Domain\Subscription\Models\Subscription;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TransitionToDegradedJob extends Command
{
    protected $signature = 'subscription:transition-to-degraded';

    protected $description = 'Transition grace subscriptions past their grace period to degraded status';

    public function handle(TransitionSubscriptionAction $transitionAction): int
    {
        $subscriptions = Subscription::where('status', SubscriptionStatus::GRACE)
            ->where('grace_period_end', '<', now())
            ->with('plan')
            ->get();

        $transitioned = 0;
        $skipped = 0;

        foreach ($subscriptions as $subscription) {
            // Idempotency: skip if already transitioned (re-check fresh status)
            $fresh = $subscription->fresh();
            if ($fresh->status !== SubscriptionStatus::GRACE) {
                $skipped++;
                continue;
            }

            try {
                // Set degraded_period_end based on plan's degraded_period_days
                $degradedPeriodDays = $fresh->plan?->degraded_period_days
                    ?? config('subscription.default_degraded_period_days', 14);

                $fresh->update([
                    'degraded_period_end' => now()->addDays($degradedPeriodDays),
                ]);

                $transitionAction->execute($fresh, SubscriptionStatus::DEGRADED, 'Grace period ended, entering degraded mode');

                $transitioned++;
            } catch (\Throwable $e) {
                Log::error('Failed to transition subscription to degraded', [
                    'subscription_id' => $fresh->id,
                    'store_id' => $fresh->store_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('TransitionToDegradedJob completed', [
            'transitioned' => $transitioned,
            'skipped' => $skipped,
            'total_found' => $subscriptions->count(),
        ]);

        $this->info("Transitioned {$transitioned} subscriptions to degraded. Skipped {$skipped}.");

        return self::SUCCESS;
    }
}
