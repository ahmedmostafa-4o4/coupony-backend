<?php

namespace Tests\Property;

use App\Domain\Store\Models\Store;
use App\Domain\Subscription\Enums\SubscriptionStatus;
use App\Domain\Subscription\Models\Subscription;
use App\Domain\Subscription\Models\SubscriptionAuditLog;
use App\Domain\Subscription\Models\SubscriptionPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Feature: subscription-system, Property 23: Expiring soon notification threshold
 *
 * **Validates: Requirements 9.6, 14.4**
 *
 * For any active subscription where the number of days until `current_period_end`
 * is less than or equal to the configured `expiring_soon_days` threshold, the
 * expiring notification job must send a `subscription_expiring_soon` notification.
 */
class ExpiringSoonNotificationThresholdPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Data provider that generates 100+ iterations with random thresholds and days remaining.
     *
     * For each iteration:
     * - Set a random threshold (1-14 days)
     * - Create a subscription with `current_period_end` at a random number of days from now
     * - Run the `subscription:send-expiring-notifications` command
     * - Verify: if days_remaining <= threshold, notification audit log is created;
     *   if days_remaining > threshold, no notification
     *
     * @return array<int, array{0: int, 1: int}>
     */
    public static function randomThresholdAndDaysProvider(): array
    {
        $faker = \Faker\Factory::create();
        $iterations = 110;
        $cases = [];

        for ($i = 0; $i < $iterations; $i++) {
            $threshold = $faker->numberBetween(1, 14);
            // Days remaining: 0 means expires today, up to threshold + 5 to test both sides
            $daysRemaining = $faker->numberBetween(1, $threshold + 5);
            $cases["iteration_{$i}_threshold_{$threshold}_days_{$daysRemaining}"] = [$threshold, $daysRemaining];
        }

        return $cases;
    }

    /**
     * @dataProvider randomThresholdAndDaysProvider
     */
    public function test_expiring_soon_notification_respects_threshold(
        int $threshold,
        int $daysRemaining
    ): void {
        // Arrange: Set the configured threshold
        config(['subscription.expiring_soon_days' => $threshold]);

        // Create a store with an owner
        $store = Store::factory()->create();
        $plan = SubscriptionPlan::factory()->create();

        // Create an active subscription with current_period_end at daysRemaining from now
        $subscription = Subscription::create([
            'store_id' => $store->id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::ACTIVE,
            'billing_cycle' => 'monthly',
            'current_period_start' => now()->subDays(30),
            'current_period_end' => now()->addDays($daysRemaining),
        ]);

        // Act: Run the expiring notifications command
        $this->artisan('subscription:send-expiring-notifications')
            ->assertSuccessful();

        // Assert: Check if audit log was created based on threshold comparison
        $auditLogExists = SubscriptionAuditLog::where('subscription_id', $subscription->id)
            ->where('event_type', 'expiring_soon_notification')
            ->exists();

        if ($daysRemaining <= $threshold) {
            // Should have sent notification (audit log created)
            $this->assertTrue(
                $auditLogExists,
                "Expected notification audit log when days_remaining ({$daysRemaining}) <= threshold ({$threshold})"
            );

            // Verify the audit log has correct data
            $auditLog = SubscriptionAuditLog::where('subscription_id', $subscription->id)
                ->where('event_type', 'expiring_soon_notification')
                ->first();

            $this->assertEquals($store->id, $auditLog->store_id);
            $this->assertEquals($subscription->status->value, $auditLog->previous_status);
            $this->assertEquals($subscription->status->value, $auditLog->new_status);
            $this->assertStringContainsString((string) $threshold, $auditLog->reason);
        } else {
            // Should NOT have sent notification (no audit log)
            $this->assertFalse(
                $auditLogExists,
                "Expected NO notification audit log when days_remaining ({$daysRemaining}) > threshold ({$threshold})"
            );
        }
    }
}
