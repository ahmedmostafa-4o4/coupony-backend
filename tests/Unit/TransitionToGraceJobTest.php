<?php

namespace Tests\Unit;

use App\Domain\Store\Models\Store;
use App\Domain\Subscription\Enums\SubscriptionStatus;
use App\Domain\Subscription\Models\Subscription;
use App\Domain\Subscription\Models\SubscriptionPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TransitionToGraceJobTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('expiredCancelledServiceStatusProvider')]
    public function test_expired_cancelled_subscription_is_archived(SubscriptionStatus $status): void
    {
        $plan = SubscriptionPlan::factory()->create();
        $subscription = Subscription::create([
            'store_id' => Store::factory()->create()->id,
            'plan_id' => $plan->id,
            'status' => $status,
            'billing_cycle' => 'monthly',
            'current_period_start' => now()->subMonths(2),
            'current_period_end' => now()->subMinute(),
            'cancelled_at' => now()->subDays(5),
        ]);

        $this->artisan('subscription:transition-to-grace')->assertExitCode(0);

        $subscription->refresh();
        $this->assertEquals(SubscriptionStatus::ARCHIVED, $subscription->status);
        $this->assertNull($subscription->grace_period_end);
    }

    public function test_expired_cancelled_trial_subscription_is_archived(): void
    {
        $plan = SubscriptionPlan::factory()->create();
        $subscription = Subscription::create([
            'store_id' => Store::factory()->create()->id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::TRIAL,
            'billing_cycle' => 'monthly',
            'trial_ends_at' => now()->subMinute(),
            'cancelled_at' => now()->subDays(5),
        ]);

        $this->artisan('subscription:transition-to-grace')->assertExitCode(0);

        $subscription->refresh();
        $this->assertEquals(SubscriptionStatus::ARCHIVED, $subscription->status);
    }

    public function test_expired_uncancelled_active_subscription_still_enters_grace(): void
    {
        $plan = SubscriptionPlan::factory()->create([
            'grace_period_days' => 7,
        ]);
        $subscription = Subscription::create([
            'store_id' => Store::factory()->create()->id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::ACTIVE,
            'billing_cycle' => 'monthly',
            'current_period_start' => now()->subMonths(2),
            'current_period_end' => now()->subMinute(),
        ]);

        $this->artisan('subscription:transition-to-grace')->assertExitCode(0);

        $subscription->refresh();
        $this->assertEquals(SubscriptionStatus::GRACE, $subscription->status);
        $this->assertNotNull($subscription->grace_period_end);
    }

    public static function expiredCancelledServiceStatusProvider(): array
    {
        return [
            'active' => [SubscriptionStatus::ACTIVE],
            'grace' => [SubscriptionStatus::GRACE],
            'degraded' => [SubscriptionStatus::DEGRADED],
        ];
    }
}
