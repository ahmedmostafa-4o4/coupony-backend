<?php

namespace Tests\Property;

use App\Domain\Store\Models\Store;
use App\Domain\Subscription\Enums\SubscriptionStatus;
use App\Domain\Subscription\Models\Subscription;
use App\Domain\Subscription\Models\SubscriptionAuditLog;
use App\Domain\Subscription\Models\SubscriptionPlan;
use App\Domain\Subscription\Services\SubscriptionStateMachine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature: subscription-system, Property 13: Audit log completeness
 *
 * **Validates: Requirements 9.7, 11.6**
 *
 * For any subscription status transition, an audit log entry must be created
 * containing: timestamp, store_id, previous status, new status, and trigger reason.
 */
class AuditLogCompletenessPropertyTest extends TestCase
{
    use RefreshDatabase;

    private SubscriptionStateMachine $stateMachine;

    /**
     * All valid transitions as defined by the state machine.
     */
    private const ALLOWED_TRANSITIONS = [
        'none' => ['trial', 'active'],
        'trial' => ['active', 'none'],
        'active' => ['grace'],
        'grace' => ['active', 'degraded'],
        'degraded' => ['active', 'suspended'],
        'suspended' => ['active', 'archived'],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->stateMachine = new SubscriptionStateMachine();
    }

    /**
     * Data provider that generates 100+ random valid transitions with Faker-generated reasons.
     *
     * @return array<int, array{0: string, 1: string, 2: string}>
     */
    public static function randomValidTransitionsProvider(): array
    {
        $faker = \Faker\Factory::create();
        $iterations = 100;
        $cases = [];

        $allTransitions = [];
        foreach (self::ALLOWED_TRANSITIONS as $from => $targets) {
            foreach ($targets as $to) {
                $allTransitions[] = [$from, $to];
            }
        }

        for ($i = 0; $i < $iterations; $i++) {
            [$from, $to] = $faker->randomElement($allTransitions);
            $reason = $faker->sentence();
            $cases["iteration_{$i}_from_{$from}_to_{$to}"] = [$from, $to, $reason];
        }

        return $cases;
    }

    /**
     * @dataProvider randomValidTransitionsProvider
     */
    public function test_audit_log_entry_is_created_with_all_required_fields(
        string $fromStatus,
        string $toStatus,
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
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
        ]);

        // Act
        $this->stateMachine->transition(
            $subscription,
            SubscriptionStatus::from($toStatus),
            $reason
        );

        // Assert - Verify audit log entry exists with all required fields
        $auditLog = SubscriptionAuditLog::where('subscription_id', $subscription->id)
            ->where('event_type', 'status_change')
            ->latest('created_at')
            ->first();

        $this->assertNotNull($auditLog, 'Audit log entry must be created for every status transition.');

        // Verify timestamp (created_at) is present and recent
        $this->assertNotNull($auditLog->created_at, 'Audit log must contain a timestamp.');
        $this->assertTrue(
            $auditLog->created_at->diffInSeconds(now()) < 5,
            'Audit log timestamp must be recent (within 5 seconds of now).'
        );

        // Verify store_id is present and correct
        $this->assertNotNull($auditLog->store_id, 'Audit log must contain store_id.');
        $this->assertEquals(
            $store->id,
            $auditLog->store_id,
            'Audit log store_id must match the subscription\'s store.'
        );

        // Verify previous status is present and correct
        $this->assertNotNull($auditLog->previous_status, 'Audit log must contain previous_status.');
        $this->assertEquals(
            $fromStatus,
            $auditLog->previous_status,
            'Audit log previous_status must match the status before transition.'
        );

        // Verify new status is present and correct
        $this->assertNotNull($auditLog->new_status, 'Audit log must contain new_status.');
        $this->assertEquals(
            $toStatus,
            $auditLog->new_status,
            'Audit log new_status must match the status after transition.'
        );

        // Verify trigger reason is present and correct
        $this->assertNotNull($auditLog->reason, 'Audit log must contain a trigger reason.');
        $this->assertEquals(
            $reason,
            $auditLog->reason,
            'Audit log reason must match the trigger reason provided.'
        );
    }
}
