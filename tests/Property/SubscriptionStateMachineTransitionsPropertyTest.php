<?php

namespace Tests\Property;

use App\Domain\Store\Models\Store;
use App\Domain\Subscription\Enums\BillingCycle;
use App\Domain\Subscription\Enums\SubscriptionStatus;
use App\Domain\Subscription\Exceptions\InvalidStateTransitionException;
use App\Domain\Subscription\Models\Subscription;
use App\Domain\Subscription\Models\SubscriptionAuditLog;
use App\Domain\Subscription\Models\SubscriptionPlan;
use App\Domain\Subscription\Services\SubscriptionStateMachine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Property 11: Lifecycle state transitions
 *
 * For any subscription with status `active` where `current_period_end < now`,
 * the transition job must change status to `grace`.
 * For any subscription with status `grace` where `grace_period_end < now`,
 * the transition job must change status to `degraded`.
 * For any subscription with status `degraded` where `degraded_period_end < now`,
 * the transition job must change status to `suspended`.
 *
 * Additionally validates that:
 * - All allowed transitions are correctly executed
 * - All disallowed transitions are rejected
 * - Each valid transition creates an audit log entry
 *
 * **Validates: Requirements 9.2, 9.3, 9.4, 14.1, 14.2, 14.3**
 *
 * @group Feature: subscription-system, Property 11: Lifecycle state transitions
 */
class SubscriptionStateMachineTransitionsPropertyTest extends TestCase
{
    use RefreshDatabase;

    private SubscriptionStateMachine $stateMachine;

    private const ALLOWED_TRANSITIONS = [
        'none' => ['trial', 'active'],
        'trial' => ['active', 'none'],
        'active' => ['grace'],
        'grace' => ['active', 'degraded'],
        'degraded' => ['active', 'suspended'],
        'suspended' => ['active', 'archived'],
        'archived' => [],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->stateMachine = new SubscriptionStateMachine();
    }

    /**
     * Data provider generating 100+ random allowed transitions with Faker-generated context.
     */
    public static function allowedTransitionsProvider(): array
    {
        $iterations = 100;
        $cases = [];
        $faker = \Faker\Factory::create();

        $allowedTransitions = [
            ['none', 'trial'],
            ['none', 'active'],
            ['trial', 'active'],
            ['trial', 'none'],
            ['active', 'grace'],
            ['grace', 'active'],
            ['grace', 'degraded'],
            ['degraded', 'active'],
            ['degraded', 'suspended'],
            ['suspended', 'active'],
            ['suspended', 'archived'],
        ];

        for ($i = 0; $i < $iterations; $i++) {
            $transition = $allowedTransitions[array_rand($allowedTransitions)];
            $reason = $faker->sentence();
            $billingCycle = $faker->randomElement(['monthly', 'yearly']);

            $cases["iteration_{$i}_from_{$transition[0]}_to_{$transition[1]}"] = [
                $transition[0],
                $transition[1],
                $reason,
                $billingCycle,
            ];
        }

        return $cases;
    }

    /**
     * Data provider generating 100+ random disallowed transitions.
     */
    public static function disallowedTransitionsProvider(): array
    {
        $iterations = 100;
        $cases = [];
        $faker = \Faker\Factory::create();

        $allStatuses = ['none', 'trial', 'active', 'grace', 'degraded', 'suspended', 'archived'];
        $allowedMap = [
            'none' => ['trial', 'active'],
            'trial' => ['active', 'none'],
            'active' => ['grace'],
            'grace' => ['active', 'degraded'],
            'degraded' => ['active', 'suspended'],
            'suspended' => ['active', 'archived'],
            'archived' => [],
        ];

        $disallowedTransitions = [];
        foreach ($allStatuses as $from) {
            foreach ($allStatuses as $to) {
                if ($from === $to) {
                    continue;
                }
                if (! in_array($to, $allowedMap[$from], true)) {
                    $disallowedTransitions[] = [$from, $to];
                }
            }
        }

        for ($i = 0; $i < $iterations; $i++) {
            $transition = $disallowedTransitions[array_rand($disallowedTransitions)];
            $reason = $faker->sentence();
            $billingCycle = $faker->randomElement(['monthly', 'yearly']);

            $cases["iteration_{$i}_from_{$transition[0]}_to_{$transition[1]}"] = [
                $transition[0],
                $transition[1],
                $reason,
                $billingCycle,
            ];
        }

        return $cases;
    }

    /**
     * Data provider generating 100+ lifecycle transition scenarios
     * (active→grace, grace→degraded, degraded→suspended).
     */
    public static function lifecycleTransitionsProvider(): array
    {
        $iterations = 100;
        $cases = [];
        $faker = \Faker\Factory::create();

        $lifecycleTransitions = [
            [
                'from' => 'active',
                'to' => 'grace',
                'reason' => 'Subscription period expired',
                'period_field' => 'current_period_end',
            ],
            [
                'from' => 'grace',
                'to' => 'degraded',
                'reason' => 'Grace period expired',
                'period_field' => 'grace_period_end',
            ],
            [
                'from' => 'degraded',
                'to' => 'suspended',
                'reason' => 'Degraded period expired',
                'period_field' => 'degraded_period_end',
            ],
        ];

        for ($i = 0; $i < $iterations; $i++) {
            $transition = $lifecycleTransitions[array_rand($lifecycleTransitions)];
            $daysExpired = $faker->numberBetween(1, 60);
            $billingCycle = $faker->randomElement(['monthly', 'yearly']);
            $gracePeriodDays = $faker->numberBetween(3, 14);
            $degradedPeriodDays = $faker->numberBetween(7, 30);

            $cases["iteration_{$i}_{$transition['from']}_to_{$transition['to']}_expired_{$daysExpired}d"] = [
                $transition['from'],
                $transition['to'],
                $transition['reason'],
                $transition['period_field'],
                $daysExpired,
                $billingCycle,
                $gracePeriodDays,
                $degradedPeriodDays,
            ];
        }

        return $cases;
    }

    /**
     * Property: For any allowed transition, the state machine must successfully
     * execute the transition and update the subscription status.
     *
     * @dataProvider allowedTransitionsProvider
     */
    public function test_allowed_transitions_are_executed_successfully(
        string $fromStatus,
        string $toStatus,
        string $reason,
        string $billingCycle,
    ): void {
        $store = Store::factory()->create();
        $plan = SubscriptionPlan::factory()->create();

        $subscription = Subscription::create([
            'store_id' => $store->id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::from($fromStatus),
            'billing_cycle' => $billingCycle,
            'current_period_start' => now()->subMonth(),
            'current_period_end' => now()->addMonth(),
            'grace_period_end' => $fromStatus === 'grace' ? now()->addDays(7) : null,
            'degraded_period_end' => $fromStatus === 'degraded' ? now()->addDays(14) : null,
        ]);

        $from = SubscriptionStatus::from($fromStatus);
        $to = SubscriptionStatus::from($toStatus);

        // Verify canTransition returns true
        $this->assertTrue(
            $this->stateMachine->canTransition($from, $to),
            "canTransition should return true for {$fromStatus} → {$toStatus}"
        );

        // Execute the transition
        $result = $this->stateMachine->transition($subscription, $to, $reason);

        // Verify the subscription status was updated
        $this->assertEquals(
            $to,
            $result->status,
            "Subscription status should be '{$toStatus}' after transition from '{$fromStatus}'"
        );

        // Verify the database was updated
        $this->assertDatabaseHas('subscriptions', [
            'id' => $subscription->id,
            'status' => $toStatus,
        ]);
    }

    /**
     * Property: For any disallowed transition, the state machine must reject
     * the transition with an InvalidStateTransitionException.
     *
     * @dataProvider disallowedTransitionsProvider
     */
    public function test_disallowed_transitions_are_rejected(
        string $fromStatus,
        string $toStatus,
        string $reason,
        string $billingCycle,
    ): void {
        $store = Store::factory()->create();
        $plan = SubscriptionPlan::factory()->create();

        $subscription = Subscription::create([
            'store_id' => $store->id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::from($fromStatus),
            'billing_cycle' => $billingCycle,
            'current_period_start' => now()->subMonth(),
            'current_period_end' => now()->addMonth(),
        ]);

        $from = SubscriptionStatus::from($fromStatus);
        $to = SubscriptionStatus::from($toStatus);

        // Verify canTransition returns false
        $this->assertFalse(
            $this->stateMachine->canTransition($from, $to),
            "canTransition should return false for {$fromStatus} → {$toStatus}"
        );

        // Verify transition throws exception
        $this->expectException(InvalidStateTransitionException::class);
        $this->stateMachine->transition($subscription, $to, $reason);
    }

    /**
     * Property: For any valid transition, an audit log entry must be created
     * containing store_id, subscription_id, previous status, new status, and reason.
     *
     * @dataProvider allowedTransitionsProvider
     */
    public function test_valid_transitions_create_audit_log_entry(
        string $fromStatus,
        string $toStatus,
        string $reason,
        string $billingCycle,
    ): void {
        $store = Store::factory()->create();
        $plan = SubscriptionPlan::factory()->create();

        $subscription = Subscription::create([
            'store_id' => $store->id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::from($fromStatus),
            'billing_cycle' => $billingCycle,
            'current_period_start' => now()->subMonth(),
            'current_period_end' => now()->addMonth(),
            'grace_period_end' => $fromStatus === 'grace' ? now()->addDays(7) : null,
            'degraded_period_end' => $fromStatus === 'degraded' ? now()->addDays(14) : null,
        ]);

        $to = SubscriptionStatus::from($toStatus);

        $this->stateMachine->transition($subscription, $to, $reason);

        // Verify audit log was created with correct data
        $this->assertDatabaseHas('subscription_audit_logs', [
            'store_id' => $store->id,
            'subscription_id' => $subscription->id,
            'event_type' => 'status_change',
            'previous_status' => $fromStatus,
            'new_status' => $toStatus,
            'reason' => $reason,
        ]);

        // Verify exactly one audit log entry was created for this transition
        $auditLogCount = SubscriptionAuditLog::where('subscription_id', $subscription->id)->count();
        $this->assertEquals(1, $auditLogCount, 'Exactly one audit log entry should be created per transition');
    }

    /**
     * Property: For any subscription with status `active` where `current_period_end < now`,
     * the state machine must allow transition to `grace`.
     * For any subscription with status `grace` where `grace_period_end < now`,
     * the state machine must allow transition to `degraded`.
     * For any subscription with status `degraded` where `degraded_period_end < now`,
     * the state machine must allow transition to `suspended`.
     *
     * This validates the lifecycle transitions that scheduled jobs would perform.
     *
     * @dataProvider lifecycleTransitionsProvider
     */
    public function test_lifecycle_transitions_execute_correctly_when_period_expired(
        string $fromStatus,
        string $toStatus,
        string $reason,
        string $periodField,
        int $daysExpired,
        string $billingCycle,
        int $gracePeriodDays,
        int $degradedPeriodDays,
    ): void {
        $store = Store::factory()->create();
        $plan = SubscriptionPlan::factory()->create([
            'grace_period_days' => $gracePeriodDays,
            'degraded_period_days' => $degradedPeriodDays,
        ]);

        // Build subscription attributes with the relevant period expired
        $attributes = [
            'store_id' => $store->id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::from($fromStatus),
            'billing_cycle' => $billingCycle,
            'current_period_start' => now()->subMonths(2),
            'current_period_end' => now()->subDays($daysExpired),
        ];

        // Set the relevant period end to be in the past (expired)
        if ($periodField === 'grace_period_end') {
            $attributes['grace_period_end'] = now()->subDays($daysExpired);
        } elseif ($periodField === 'degraded_period_end') {
            $attributes['degraded_period_end'] = now()->subDays($daysExpired);
        }

        $subscription = Subscription::create($attributes);

        $from = SubscriptionStatus::from($fromStatus);
        $to = SubscriptionStatus::from($toStatus);

        // Verify the period is indeed expired (simulating what the scheduled job checks)
        $periodEndValue = $subscription->{$periodField};
        $this->assertTrue(
            $periodEndValue->isPast(),
            "The {$periodField} should be in the past (expired by {$daysExpired} days)"
        );

        // Verify the transition is allowed
        $this->assertTrue(
            $this->stateMachine->canTransition($from, $to),
            "Lifecycle transition from {$fromStatus} to {$toStatus} should be allowed"
        );

        // Execute the transition (as the scheduled job would)
        $result = $this->stateMachine->transition($subscription, $to, $reason);

        // Verify the status was updated correctly
        $this->assertEquals(
            $to,
            $result->status,
            "After lifecycle transition, status should be '{$toStatus}'"
        );

        // Verify audit log was created
        $this->assertDatabaseHas('subscription_audit_logs', [
            'subscription_id' => $subscription->id,
            'event_type' => 'status_change',
            'previous_status' => $fromStatus,
            'new_status' => $toStatus,
            'reason' => $reason,
        ]);
    }
}
