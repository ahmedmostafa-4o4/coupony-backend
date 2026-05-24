<?php

namespace Tests\Property;

use App\Domain\Store\Models\Store;
use App\Domain\Subscription\Enums\HistoryStatus;
use App\Domain\Subscription\Models\SubscriptionHistory;
use App\Domain\Subscription\Models\SubscriptionPlan;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Feature: subscription-system, Property 19: History filter correctness
 *
 * **Validates: Requirements 7.3**
 *
 * For any History_Status filter value applied to the subscription history endpoint,
 * every record in the response must have a status matching the filter value.
 */
class HistoryFilterCorrectnessPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Data provider that generates 100+ iterations with random filter values
     * and varying numbers of history records with mixed statuses.
     *
     * @return array<string, array{0: string, 1: int, 2: int}>
     */
    public static function randomHistoryFilterProvider(): array
    {
        $faker = \Faker\Factory::create();
        $iterations = 105;
        $cases = [];

        $statuses = array_column(HistoryStatus::cases(), 'value');

        for ($i = 0; $i < $iterations; $i++) {
            $filterStatus = $faker->randomElement($statuses);
            // Number of records matching the filter (1-10)
            $matchingCount = $faker->numberBetween(1, 10);
            // Number of records NOT matching the filter (0-10)
            $nonMatchingCount = $faker->numberBetween(0, 10);

            $cases["iteration_{$i}_filter_{$filterStatus}_match_{$matchingCount}_other_{$nonMatchingCount}"] = [
                $filterStatus,
                $matchingCount,
                $nonMatchingCount,
            ];
        }

        return $cases;
    }

    /**
     * @dataProvider randomHistoryFilterProvider
     */
    public function test_history_filter_returns_only_records_matching_filter_status(
        string $filterStatus,
        int $matchingCount,
        int $nonMatchingCount
    ): void {
        // Arrange: Create store with owner
        $owner = User::factory()->create();
        $store = Store::factory()->create(['owner_user_id' => $owner->id]);
        $plan = SubscriptionPlan::factory()->create();

        // Create history records that match the filter
        for ($i = 0; $i < $matchingCount; $i++) {
            SubscriptionHistory::create([
                'store_id' => $store->id,
                'plan_id' => $plan->id,
                'billing_cycle' => fake()->randomElement(['monthly', 'yearly']),
                'amount' => fake()->randomFloat(2, 49, 499),
                'payment_method' => fake()->randomElement(['card', 'wallet']),
                'status' => $filterStatus,
                'period_start' => now()->subMonths($i + 1),
                'period_end' => now()->subMonths($i),
            ]);
        }

        // Create history records that do NOT match the filter
        $otherStatuses = array_filter(
            array_column(HistoryStatus::cases(), 'value'),
            fn ($s) => $s !== $filterStatus
        );

        for ($i = 0; $i < $nonMatchingCount; $i++) {
            SubscriptionHistory::create([
                'store_id' => $store->id,
                'plan_id' => $plan->id,
                'billing_cycle' => fake()->randomElement(['monthly', 'yearly']),
                'amount' => fake()->randomFloat(2, 49, 499),
                'payment_method' => fake()->randomElement(['card', 'wallet']),
                'status' => fake()->randomElement($otherStatuses),
                'period_start' => now()->subMonths($i + 1),
                'period_end' => now()->subMonths($i),
            ]);
        }

        // Act: Authenticate as owner and request history with filter
        Sanctum::actingAs($owner);

        $response = $this->getJson(
            "/api/v1/stores/{$store->id}/subscription/history?status={$filterStatus}"
        );

        // Assert: Response is successful
        $response->assertStatus(200);

        $data = $response->json('data');

        // Assert: Every record in the response matches the filter status
        $this->assertNotEmpty($data, 'Response should contain at least one record when matching records exist.');

        foreach ($data as $index => $record) {
            $this->assertEquals(
                $filterStatus,
                $record['status'],
                "Record at index {$index} has status '{$record['status']}' but filter was '{$filterStatus}'."
            );
        }

        // Assert: The count of returned records equals the matching count
        $this->assertCount(
            $matchingCount,
            $data,
            "Expected {$matchingCount} records matching filter '{$filterStatus}', got " . count($data)
        );
    }
}
