<?php

namespace App\Domain\Subscription\Jobs;

use App\Domain\Subscription\Actions\TransitionSubscriptionAction;
use App\Domain\Subscription\Enums\SubscriptionStatus;
use App\Domain\Subscription\Models\Subscription;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TransitionToGraceJob extends Command
{
    protected $signature = 'subscription:transition-to-grace';

    protected $description = 'Transition expired subscriptions through grace or cancellation';

    public function handle(TransitionSubscriptionAction $transitionAction): int
    {
        $subscriptions = Subscription::where(function ($query) {
            $query->where(function ($activeQuery) {
                $activeQuery->where('status', SubscriptionStatus::ACTIVE)
                    ->where('current_period_end', '<', now());
            })->orWhere(function ($cancelledQuery) {
                $cancelledQuery->whereNotNull('cancelled_at')
                    ->whereIn('status', [
                        SubscriptionStatus::GRACE,
                        SubscriptionStatus::DEGRADED,
                    ])
                    ->where('current_period_end', '<', now());
            })->orWhere(function ($cancelledTrialQuery) {
                $cancelledTrialQuery->whereNotNull('cancelled_at')
                    ->where('status', SubscriptionStatus::TRIAL)
                    ->where('trial_ends_at', '<', now());
            });
        })
            ->with('plan')
            ->get();

        $transitioned = 0;
        $skipped = 0;

        foreach ($subscriptions as $subscription) {
            // Idempotency: skip if already transitioned (re-check fresh status)
            $fresh = $subscription->fresh();
            if ($fresh->status !== SubscriptionStatus::ACTIVE
                && ! ($fresh->cancelled_at !== null && $fresh->status->canCancelAtPeriodEnd())) {
                $skipped++;

                continue;
            }

            try {
                if ($fresh->cancelled_at !== null) {
                    $transitionAction->execute(
                        $fresh,
                        SubscriptionStatus::ARCHIVED,
                        'Subscription cancelled at period end'
                    );

                    $transitioned++;

                    continue;
                }

                // Set grace_period_end based on plan's grace_period_days
                $gracePeriodDays = $fresh->plan?->grace_period_days
                    ?? config('subscription.default_grace_period_days', 7);

                $fresh->update([
                    'grace_period_end' => now()->addDays($gracePeriodDays),
                ]);

                $transitionAction->execute($fresh, SubscriptionStatus::GRACE, 'Subscription period ended, entering grace period');

                $transitioned++;
            } catch (\Throwable $e) {
                Log::error('Failed to transition subscription to grace', [
                    'subscription_id' => $fresh->id,
                    'store_id' => $fresh->store_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('TransitionToGraceJob completed', [
            'transitioned' => $transitioned,
            'skipped' => $skipped,
            'total_found' => $subscriptions->count(),
        ]);

        $this->info("Processed {$transitioned} expired subscriptions. Skipped {$skipped}.");

        return self::SUCCESS;
    }
}
