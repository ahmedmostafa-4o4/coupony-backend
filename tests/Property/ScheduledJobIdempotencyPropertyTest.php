<?php

namespace Tests\Property;

use App\Domain\Store\Models\Store;
use App\Domain\Subscription\Enums\SubscriptionStatus;
use App\Domain\Subscription\Models\Subscription;
use App\Domain\Subscription\Models\SubscriptionAuditLog;
use App\Domain\Subscription\Models\SubscriptionPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/**
 * Feature: subscription-system, Property 21: Scheduled job idempotency
 *
 * **Validates: Requirements 14.5**
 *
 * For any set of subscriptions in various lifecycle states, running each scheduled
 * transition job N times must produce the same final state as running it exactly once.
 */
class ScheduledJobIdempotencyPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The three scheduled transition commands to test.
     */
    private const TRANSITION_COMMANDS = [
        'subscription:transition-to-grace',
        'subscription:transition-to-degraded',
        'subscription:transition-to-suspended',
    ];

    /**
     * Data provider that generates 100+ random scenarios with various subscription states.
     *
     * Each scenario defines a set of subscriptions with different statuses and eligibility
     * for transition, plus a random number of job executions (2-5).
     *
     * @return array<int, array{0: string, 1: array, 2: int}>
     */
    public static function randomScheduledJobScenariosProvider(): array
    {
        $faker = \Faker\Factory::create();
        $iterations = 105;
        $cases = [];

        for ($i = 0; $i < $iterations; $i++) {
            // Pick a random command to test
            $command = $faker->randomElement(self::TRANSITION_COMMANDS);

            // Generate a random number of subscriptions (1-5) with various states
            $subscriptionCount = $faker->numberBetween(1, 5);
            $subscriptions = [];

            for ($j = 0; $j < $subscriptionCount; $j++) {
                $subscriptions[] = self::generateSubscriptionScenario($faker, $command);
            }

            // Random number of repeated executions (2-5)
            $executionCount = $faker->numberBetween(2, 5);

            $cases["iteration_{$i}_{$command}_x{$executionCount}"] = [
                $command,
                $subscriptions,
                $executionCount,
            ];
        }

        return $cases;
    }

    /**
     * Generate a subscription scenario for a given command.
     * Some subscriptions will be eligible for transition, some won't.
     */
    private static function generateSubscriptionScenario(\Faker\Generator $faker, string $command): array
    {
        // Determine if this subscription should be eligible for transition
        $eligible = $faker->boolean(60); // 60% chance of being eligible

        switch ($command) {
            case 'subscription:transition-to-grace':
                if ($eligible) {
                    return [
                        'status' => 'active',
                        'current_period_end' => now()->subDays($faker->numberBetween(1, 30))->toISOString(),
                        'grace_period_end' => null,
                        'degraded_period_end' => null,
                    ];
                }
                // Not eligible - could be active but not expired, or different status
                $status = $faker->randomElement(['active', 'grace', 'degraded', 'suspended', 'none']);
                return [
                    'status' => $status,
                    'current_period_end' => $status === 'active'
                        ? now()->addDays($faker->numberBetween(1, 30))->toISOString()
                        : now()->subDays($faker->numberBetween(1, 30))->toISOString(),
                    'grace_period_end' => $status === 'grace'
                        ? now()->addDays($faker->numberBetween(1, 14))->toISOString()
                        : null,
                    'degraded_period_end' => $status === 'degraded'
                        ? now()->addDays($faker->numberBetween(1, 14))->toISOString()
                        : null,
                ];

            case 'subscription:transition-to-degraded':
                if ($eligible) {
                    return [
                        'status' => 'grace',
                        'current_period_end' => now()->subDays($faker->numberBetween(10, 40))->toISOString(),
                        'grace_period_end' => now()->subDays($faker->numberBetween(1, 10))->toISOString(),
                        'degraded_period_end' => null,
                    ];
                }
                $status = $faker->randomElement(['active', 'grace', 'degraded', 'suspended', 'none']);
                return [
                    'status' => $status,
                    'current_period_end' => now()->subDays($faker->numberBetween(1, 30))->toISOString(),
                    'grace_period_end' => $status === 'grace'
                        ? now()->addDays($faker->numberBetween(1, 14))->toISOString()
                        : null,
                    'degraded_period_end' => $status === 'degraded'
                        ? now()->addDays($faker->numberBetween(1, 14))->toISOString()
                        : null,
                ];

            case 'subscription:transition-to-suspended':
                if ($eligible) {
                    return [
                        'status' => 'degraded',
                        'current_period_end' => now()->subDays($faker->numberBetween(20, 60))->toISOString(),
                        'grace_period_end' => now()->subDays($faker->numberBetween(10, 20))->toISOString(),
                        'degraded_period_end' => now()->subDays($faker->numberBetween(1, 10))->toISOString(),
                    ];
                }
                $status = $faker->randomElement(['active', 'grace', 'degraded', 'suspended', 'none']);
                return [
                    'status' => $status,
                    'current_period_end' => now()->subDays($faker->numberBetween(1, 30))->toISOString(),
                    'grace_period_end' => $status === 'grace'
                        ? now()->addDays($faker->numberBetween(1, 14))->toISOString()
                        : ($status === 'degraded' ? now()->subDays($faker->numberBetween(1, 10))->toISOString() : null),
                    'degraded_period_end' => $status === 'degraded'
                        ? now()->addDays($faker->numberBetween(1, 14))->toISOString()
                        : null,
                ];

            default:
                return [
                    'status' => 'active',
                    'current_period_end' => now()->addDays(30)->toISOString(),
                    'grace_period_end' => null,
                    'degraded_period_end' => null,
                ];
        }
    }

    /**
     * @dataProvider randomScheduledJobScenariosProvider
     */
    public function test_scheduled_job_produces_same_state_regardless_of_execution_count(
        string $command,
        array $subscriptionScenarios,
        int $executionCount
    ): void {
        // Arrange - Create plan and subscriptions
        $plan = SubscriptionPlan::factory()->create();

        $subscriptionIds = [];
        foreach ($subscriptionScenarios as $scenario) {
            $store = Store::factory()->create();
            $subscription = Subscription::create([
                'store_id' => $store->id,
                'plan_id' => $plan->id,
                'status' => SubscriptionStatus::from($scenario['status']),
                'billing_cycle' => 'monthly',
                'current_period_start' => now()->subMonths(2),
                'current_period_end' => $scenario['current_period_end'],
                'grace_period_end' => $scenario['grace_period_end'],
                'degraded_period_end' => $scenario['degraded_period_end'],
            ]);
            $subscriptionIds[] = $subscription->id;
        }

        // Act - Run the command once and capture the state
        Artisan::call($command);

        $stateAfterOnce = $this->captureSubscriptionStates($subscriptionIds);
        $auditCountAfterOnce = SubscriptionAuditLog::whereIn('subscription_id', $subscriptionIds)->count();

        // Act - Run the command N-1 more times (total = N)
        for ($i = 1; $i < $executionCount; $i++) {
            Artisan::call($command);
        }

        $stateAfterN = $this->captureSubscriptionStates($subscriptionIds);
        $auditCountAfterN = SubscriptionAuditLog::whereIn('subscription_id', $subscriptionIds)->count();

        // Assert - Final state must be identical to state after single execution
        foreach ($subscriptionIds as $id) {
            $this->assertEquals(
                $stateAfterOnce[$id]['status'],
                $stateAfterN[$id]['status'],
                "Subscription {$id} status must be the same after running '{$command}' once vs {$executionCount} times."
            );

            $this->assertEquals(
                $stateAfterOnce[$id]['grace_period_end'],
                $stateAfterN[$id]['grace_period_end'],
                "Subscription {$id} grace_period_end must be the same after running '{$command}' once vs {$executionCount} times."
            );

            $this->assertEquals(
                $stateAfterOnce[$id]['degraded_period_end'],
                $stateAfterN[$id]['degraded_period_end'],
                "Subscription {$id} degraded_period_end must be the same after running '{$command}' once vs {$executionCount} times."
            );
        }

        // Assert - No additional audit logs created by repeated runs
        $this->assertEquals(
            $auditCountAfterOnce,
            $auditCountAfterN,
            "Running '{$command}' {$executionCount} times must not create more audit logs than running it once."
        );
    }

    /**
     * Capture the current state of all subscriptions by ID.
     *
     * @param array<string> $subscriptionIds
     * @return array<string, array{status: string, grace_period_end: ?string, degraded_period_end: ?string}>
     */
    private function captureSubscriptionStates(array $subscriptionIds): array
    {
        $states = [];
        foreach ($subscriptionIds as $id) {
            $subscription = Subscription::find($id);
            $states[$id] = [
                'status' => $subscription->status->value,
                'grace_period_end' => $subscription->grace_period_end?->toISOString(),
                'degraded_period_end' => $subscription->degraded_period_end?->toISOString(),
            ];
        }

        return $states;
    }
}
