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
 * Feature: subscription-system, Property 20: History ordering
 *
 * **Validates: Requirements 7.1**
 *
 * For any store with multiple subscription history records, the records returned
 * by the history endpoint must be ordered by created_at descending (each record's
 * created_at must be >= the next record's created_at).
 */
class HistoryOrderingPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Data provider that generates 100+ iterations with varying numbers of
     * history records created at random timestamps.
     *
     * @return array<string, array{0: int, 1: int}>
     */
    public static function randomHistoryOrderingProvider(): array
    {
        $faker = \Faker\Factory::create();
        $iterations = 105;
        $cases = [];

        for ($i = 0; $i < $iterations; $i++) {
            // Number of history records to create (2-20)
            $recordCount = $faker->numberBetween(2, 20);
            // Random seed for timestamp generation
            $seed = $faker->numberBetween(1, 100000);

            $cases["iteration_{$i}_records_{$recordCount}"] = [
                $recordCount,
                $seed,
            ];
        }

        return $cases;
    }

    /**
     * @dataProvider randomHistoryOrderingProvider
     */
    public function test_history_records_are_ordered_by_created_at_descending(
        int $recordCount,
        int $seed
    ): void {
        // Arrange: Create store with owner
        $faker = \Faker\Factory::create();
        $faker->seed($seed);

        $owner = User::factory()->create();
        $store = Store::factory()->create(['owner_user_id' => $owner->id]);
        $plan = SubscriptionPlan::factory()->create();

        $statuses = array_column(HistoryStatus::cases(), 'value');

        // Create history records with random created_at timestamps (spread over past year)
        for ($i = 0; $i < $recordCount; $i++) {
            $createdAt = now()->subDays($faker->numberBetween(1, 365))->subHours($faker->numberBetween(0, 23));

            $record = SubscriptionHistory::create([
                'store_id' => $store->id,
                'plan_id' => $plan->id,
                'billing_cycle' => $faker->randomElement(['monthly', 'yearly']),
                'amount' => $faker->randomFloat(2, 49, 499),
                'payment_method' => $faker->randomElement(['card', 'wallet']),
                'status' => $faker->randomElement($statuses),
                'period_start' => $createdAt,
                'period_end' => $createdAt->copy()->addMonth(),
            ]);

            // Manually set created_at to a random time to ensure non-sequential insertion
            $record->created_at = $createdAt;
            $record->save();
        }

        // Act: Authenticate as owner and request history
        Sanctum::actingAs($owner);

        $response = $this->getJson(
            "/api/v1/stores/{$store->id}/subscription/history?per_page=100"
        );

        // Assert: Response is successful
        $response->assertStatus(200);

        $data = $response->json('data');

        // Assert: We have the expected number of records
        $this->assertCount($recordCount, $data);

        // Assert: Records are ordered by created_at descending
        for ($i = 0; $i < count($data) - 1; $i++) {
            $currentCreatedAt = $data[$i]['created_at'];
            $nextCreatedAt = $data[$i + 1]['created_at'];

            $this->assertGreaterThanOrEqual(
                strtotime($nextCreatedAt),
                strtotime($currentCreatedAt),
                "Record at index {$i} (created_at: {$currentCreatedAt}) must be >= record at index " . ($i + 1) . " (created_at: {$nextCreatedAt}). Records must be ordered by created_at descending."
            );
        }
    }
}
