<?php

namespace App\Domain\Subscription\Jobs;

use App\Domain\Notification\Services\NotificationService;
use App\Domain\Subscription\Enums\SubscriptionStatus;
use App\Domain\Subscription\Models\Subscription;
use App\Domain\Subscription\Models\SubscriptionAuditLog;
use App\Domain\Subscription\Notifications\SubscriptionExpiringSoonNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendExpiringNotificationJob extends Command
{
    protected $signature = 'subscription:send-expiring-notifications';

    protected $description = 'Send expiring soon notifications for subscriptions nearing their renewal date';

    public function handle(NotificationService $notificationService): int
    {
        $thresholdDays = config('subscription.expiring_soon_days', 3);

        $subscriptions = Subscription::where('status', SubscriptionStatus::ACTIVE)
            ->where('current_period_end', '<=', now()->addDays($thresholdDays))
            ->where('current_period_end', '>', now())
            ->with(['store.owner', 'plan'])
            ->get();

        $sent = 0;
        $skipped = 0;

        foreach ($subscriptions as $subscription) {
            // Track sent notifications via audit log to avoid duplicates (idempotent)
            $alreadySent = SubscriptionAuditLog::where('subscription_id', $subscription->id)
                ->where('event_type', 'expiring_soon_notification')
                ->where('created_at', '>=', $subscription->current_period_start)
                ->exists();

            if ($alreadySent) {
                $skipped++;
                continue;
            }

            $store = $subscription->store;
            if (! $store || ! $store->owner) {
                $skipped++;
                continue;
            }

            try {
                $notification = new SubscriptionExpiringSoonNotification($subscription);
                $notification->send($store->owner, $notificationService);

                // Record that we sent this notification for this billing period
                SubscriptionAuditLog::create([
                    'store_id' => $subscription->store_id,
                    'subscription_id' => $subscription->id,
                    'event_type' => 'expiring_soon_notification',
                    'previous_status' => $subscription->status->value,
                    'new_status' => $subscription->status->value,
                    'reason' => "Expiring soon notification sent ({$thresholdDays} days threshold)",
                ]);

                $sent++;
            } catch (\Throwable $e) {
                Log::error('Failed to send expiring notification', [
                    'subscription_id' => $subscription->id,
                    'store_id' => $subscription->store_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('SendExpiringNotificationJob completed', [
            'sent' => $sent,
            'skipped' => $skipped,
            'total_found' => $subscriptions->count(),
        ]);

        $this->info("Sent {$sent} expiring notifications. Skipped {$skipped}.");

        return self::SUCCESS;
    }
}
