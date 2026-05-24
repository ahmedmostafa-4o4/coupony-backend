<?php

namespace App\Domain\Subscription\Jobs;

use App\Domain\Subscription\Actions\TransitionSubscriptionAction;
use App\Domain\Subscription\Enums\SubscriptionStatus;
use App\Domain\Subscription\Models\Subscription;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TransitionToSuspendedJob extends Command
{
    protected $signature = 'subscription:transition-to-suspended';

    protected $description = 'Transition degraded subscriptions past their degraded period to suspended status';

    public function handle(TransitionSubscriptionAction $transitionAction): int
    {
        $subscriptions = Subscription::where('status', SubscriptionStatus::DEGRADED)
            ->where('degraded_period_end', '<', now())
            ->get();

        $transitioned = 0;
        $skipped = 0;

        foreach ($subscriptions as $subscription) {
            // Idempotency: skip if already transitioned (re-check fresh status)
            $fresh = $subscription->fresh();
            if ($fresh->status !== SubscriptionStatus::DEGRADED) {
                $skipped++;
                continue;
            }

            try {
                $transitionAction->execute($fresh, SubscriptionStatus::SUSPENDED, 'Degraded period ended, subscription suspended');

                $transitioned++;
            } catch (\Throwable $e) {
                Log::error('Failed to transition subscription to suspended', [
                    'subscription_id' => $fresh->id,
                    'store_id' => $fresh->store_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('TransitionToSuspendedJob completed', [
            'transitioned' => $transitioned,
            'skipped' => $skipped,
            'total_found' => $subscriptions->count(),
        ]);

        $this->info("Transitioned {$transitioned} subscriptions to suspended. Skipped {$skipped}.");

        return self::SUCCESS;
    }
}
