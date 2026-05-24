<?php

namespace Tests\Unit;

use App\Domain\Store\Models\Store;
use App\Domain\Subscription\Enums\SubscriptionStatus;
use App\Domain\Subscription\Exceptions\InvalidStateTransitionException;
use App\Domain\Subscription\Models\Subscription;
use App\Domain\Subscription\Models\SubscriptionPlan;
use App\Domain\Subscription\Services\SubscriptionStateMachine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionStateMachineTest extends TestCase
{
    use RefreshDatabase;

    private SubscriptionStateMachine $stateMachine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->stateMachine = new SubscriptionStateMachine();
    }

    public function test_can_transition_returns_true_for_allowed_transitions(): void
    {
        $allowedTransitions = [
            [SubscriptionStatus::NONE, SubscriptionStatus::TRIAL],
            [SubscriptionStatus::NONE, SubscriptionStatus::ACTIVE],
            [SubscriptionStatus::TRIAL, SubscriptionStatus::ACTIVE],
            [SubscriptionStatus::TRIAL, SubscriptionStatus::NONE],
            [SubscriptionStatus::ACTIVE, SubscriptionStatus::GRACE],
            [SubscriptionStatus::GRACE, SubscriptionStatus::ACTIVE],
            [SubscriptionStatus::GRACE, SubscriptionStatus::DEGRADED],
            [SubscriptionStatus::DEGRADED, SubscriptionStatus::ACTIVE],
            [SubscriptionStatus::DEGRADED, SubscriptionStatus::SUSPENDED],
            [SubscriptionStatus::SUSPENDED, SubscriptionStatus::ACTIVE],
            [SubscriptionStatus::SUSPENDED, SubscriptionStatus::ARCHIVED],
        ];

        foreach ($allowedTransitions as [$from, $to]) {
            $this->assertTrue(
                $this->stateMachine->canTransition($from, $to),
                "Expected transition from {$from->value} to {$to->value} to be allowed."
            );
        }
    }

    public function test_can_transition_returns_false_for_disallowed_transitions(): void
    {
        $disallowedTransitions = [
            [SubscriptionStatus::NONE, SubscriptionStatus::GRACE],
            [SubscriptionStatus::NONE, SubscriptionStatus::DEGRADED],
            [SubscriptionStatus::NONE, SubscriptionStatus::SUSPENDED],
            [SubscriptionStatus::NONE, SubscriptionStatus::ARCHIVED],
            [SubscriptionStatus::ACTIVE, SubscriptionStatus::NONE],
            [SubscriptionStatus::ACTIVE, SubscriptionStatus::TRIAL],
            [SubscriptionStatus::ACTIVE, SubscriptionStatus::DEGRADED],
            [SubscriptionStatus::ACTIVE, SubscriptionStatus::SUSPENDED],
            [SubscriptionStatus::ACTIVE, SubscriptionStatus::ARCHIVED],
            [SubscriptionStatus::ARCHIVED, SubscriptionStatus::NONE],
            [SubscriptionStatus::ARCHIVED, SubscriptionStatus::TRIAL],
            [SubscriptionStatus::ARCHIVED, SubscriptionStatus::ACTIVE],
            [SubscriptionStatus::ARCHIVED, SubscriptionStatus::GRACE],
            [SubscriptionStatus::ARCHIVED, SubscriptionStatus::DEGRADED],
            [SubscriptionStatus::ARCHIVED, SubscriptionStatus::SUSPENDED],
        ];

        foreach ($disallowedTransitions as [$from, $to]) {
            $this->assertFalse(
                $this->stateMachine->canTransition($from, $to),
                "Expected transition from {$from->value} to {$to->value} to be disallowed."
            );
        }
    }

    public function test_get_allowed_transitions_returns_correct_statuses(): void
    {
        $this->assertEquals(
            [SubscriptionStatus::TRIAL, SubscriptionStatus::ACTIVE],
            $this->stateMachine->getAllowedTransitions(SubscriptionStatus::NONE)
        );

        $this->assertEquals(
            [SubscriptionStatus::ACTIVE, SubscriptionStatus::NONE],
            $this->stateMachine->getAllowedTransitions(SubscriptionStatus::TRIAL)
        );

        $this->assertEquals(
            [SubscriptionStatus::GRACE],
            $this->stateMachine->getAllowedTransitions(SubscriptionStatus::ACTIVE)
        );

        $this->assertEquals(
            [SubscriptionStatus::ACTIVE, SubscriptionStatus::DEGRADED],
            $this->stateMachine->getAllowedTransitions(SubscriptionStatus::GRACE)
        );

        $this->assertEquals(
            [SubscriptionStatus::ACTIVE, SubscriptionStatus::SUSPENDED],
            $this->stateMachine->getAllowedTransitions(SubscriptionStatus::DEGRADED)
        );

        $this->assertEquals(
            [SubscriptionStatus::ACTIVE, SubscriptionStatus::ARCHIVED],
            $this->stateMachine->getAllowedTransitions(SubscriptionStatus::SUSPENDED)
        );

        $this->assertEquals(
            [],
            $this->stateMachine->getAllowedTransitions(SubscriptionStatus::ARCHIVED)
        );
    }

    public function test_transition_updates_subscription_status(): void
    {
        $store = Store::factory()->create();
        $plan = SubscriptionPlan::factory()->create();

        $subscription = Subscription::create([
            'store_id' => $store->id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::NONE,
            'billing_cycle' => 'monthly',
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
        ]);

        $result = $this->stateMachine->transition(
            $subscription,
            SubscriptionStatus::ACTIVE,
            'Payment confirmed'
        );

        $this->assertEquals(SubscriptionStatus::ACTIVE, $result->status);
    }

    public function test_transition_creates_audit_log_entry(): void
    {
        $store = Store::factory()->create();
        $plan = SubscriptionPlan::factory()->create();

        $subscription = Subscription::create([
            'store_id' => $store->id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::ACTIVE,
            'billing_cycle' => 'monthly',
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
        ]);

        $this->stateMachine->transition(
            $subscription,
            SubscriptionStatus::GRACE,
            'Subscription period expired'
        );

        $this->assertDatabaseHas('subscription_audit_logs', [
            'store_id' => $store->id,
            'subscription_id' => $subscription->id,
            'event_type' => 'status_change',
            'previous_status' => 'active',
            'new_status' => 'grace',
            'reason' => 'Subscription period expired',
        ]);
    }

    public function test_transition_throws_exception_for_disallowed_transition(): void
    {
        $store = Store::factory()->create();
        $plan = SubscriptionPlan::factory()->create();

        $subscription = Subscription::create([
            'store_id' => $store->id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::ARCHIVED,
            'billing_cycle' => 'monthly',
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
        ]);

        $this->expectException(InvalidStateTransitionException::class);
        $this->expectExceptionMessage("Invalid subscription state transition from 'archived' to 'active'.");

        $this->stateMachine->transition(
            $subscription,
            SubscriptionStatus::ACTIVE,
            'Attempted reactivation'
        );
    }

    public function test_transition_does_not_create_audit_log_on_failure(): void
    {
        $store = Store::factory()->create();
        $plan = SubscriptionPlan::factory()->create();

        $subscription = Subscription::create([
            'store_id' => $store->id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::ACTIVE,
            'billing_cycle' => 'monthly',
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
        ]);

        try {
            $this->stateMachine->transition(
                $subscription,
                SubscriptionStatus::SUSPENDED,
                'Invalid jump'
            );
        } catch (InvalidStateTransitionException $e) {
            // Expected
        }

        $this->assertDatabaseMissing('subscription_audit_logs', [
            'subscription_id' => $subscription->id,
        ]);
    }

    public function test_invalid_state_transition_exception_exposes_from_and_to(): void
    {
        $exception = InvalidStateTransitionException::make(
            SubscriptionStatus::ACTIVE,
            SubscriptionStatus::ARCHIVED
        );

        $this->assertEquals(SubscriptionStatus::ACTIVE, $exception->getFrom());
        $this->assertEquals(SubscriptionStatus::ARCHIVED, $exception->getTo());
    }
}
