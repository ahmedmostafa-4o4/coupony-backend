<?php

namespace Tests\Property;

use App\Domain\Store\Models\Store;
use App\Domain\Subscription\Enums\SubscriptionStatus;
use App\Domain\Subscription\Models\Subscription;
use App\Domain\Subscription\Models\SubscriptionPlan;
use App\Domain\Subscription\Services\SubscriptionStateMachine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature: subscription-system, Property 12: Payment restores active status
 *
 * **Validates: Requirements 9.5**
 *
 * For any subscription with status in [grace, degraded], a successful payment
 * must transition the subscription status back to active.
 */
class PaymentRestoresActiveStatusPropertyTest extends TestCase
{
    use RefreshDatabase;

    private SubscriptionStateMachine $stateMachine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->stateMachine = new SubscriptionStateMachine();
    }

    /**
     * Data provider that generates 100+ iterations with random grace/degraded status
     * and Faker-generated reasons simulating a successful payment.
     *
     * @return array<int, array{0: string, 1: string}>
     */
    public static function randomGraceOrDegradedProvider(): array
    {
        $faker = \Faker\Factory::create();
        $iterations = 110;
        $cases = [];

        $payableStatuses = ['grace', 'degraded'];

        for ($i = 0; $i < $iterations; $i++) {
            $status = $faker->randomElement($payableStatuses);
            $reason = $faker->randomElement([
                'Payment received via Paymob webhook',
                'Successful card payment confirmed',
                'Wallet payment processed successfully',
                'Payment session confirmed by store owner',
                'Renewal payment approved',
            ]) . ' - ' . $faker->uuid();

            $cases["iteration_{$i}_from_{$status}"] = [$status, $reason];
        }

        return $cases;
    }

    /**
     * @dataProvider randomGraceOrDegradedProvider
     */
    public function test_payment_transitions_subscription_back_to_active(
        string $fromStatus,
        string $reason
    ): void {
        // Arrange
        $store = Store::factory()->create();
        $plan = SubscriptionPlan::factory()->create();

        $subscription = Subscription::create([
            'store_id' => $store->id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::from($fromStatus),
            'billing_cycle' => 'monthly',
            'current_period_start' => now()->subMonth(),
            'current_period_end' => now()->subDays(1),
            'grace_period_end' => $fromStatus === 'grace' ? now()->addDays(6) : now()->subDays(1),
            'degraded_period_end' => $fromStatus === 'degraded' ? now()->addDays(13) : null,
        ]);

        // Act - Simulate successful payment by transitioning to active
        $updatedSubscription = $this->stateMachine->transition(
            $subscription,
            SubscriptionStatus::ACTIVE,
            $reason
        );

        // Assert - Subscription must now be active
        $this->assertEquals(
            SubscriptionStatus::ACTIVE,
            $updatedSubscription->status,
            "A successful payment must transition a '{$fromStatus}' subscription back to 'active'."
        );

        // Assert - Verify the persisted state matches
        $freshSubscription = Subscription::find($subscription->id);
        $this->assertEquals(
            SubscriptionStatus::ACTIVE,
            $freshSubscription->status,
            "The active status must be persisted in the database after payment."
        );
    }
}
