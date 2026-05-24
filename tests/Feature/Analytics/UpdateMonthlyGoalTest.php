<?php

namespace Tests\Feature\Analytics;

use App\Domain\Analytics\Actions\UpdateMonthlyGoalAction;
use App\Domain\Store\Models\Store;
use App\Domain\User\Models\User;
use Faker\Factory as Faker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class UpdateMonthlyGoalTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // Property 8: Monthly Goal Persistence Round-Trip
    // =========================================================================

    /**
     * Property 8: Monthly Goal Persistence Round-Trip
     *
     * For any positive integer goal value, after calling UpdateMonthlyGoalAction,
     * querying the store's monthly_goal column SHALL return the same value that was submitted,
     * and the return value equals the submitted goal.
     *
     * **Validates: Requirements 2.1**
     */
    #[DataProvider('randomPositiveIntegersProvider')]
    public function test_monthly_goal_persistence_round_trip(int $goal): void
    {
        $store = Store::factory()->active()->create();

        $action = new UpdateMonthlyGoalAction();
        $returnValue = $action->execute($store, $goal);

        // Property: return value equals the submitted goal
        $this->assertSame($goal, $returnValue);

        // Property: querying the store's monthly_goal column returns the same value
        $store->refresh();
        $this->assertSame($goal, $store->monthly_goal);

        // Also verify via direct database query
        $this->assertDatabaseHas('stores', [
            'id' => $store->id,
            'monthly_goal' => $goal,
        ]);
    }

    /**
     * Data provider generating 100 random positive integers (1 to 999999).
     */
    public static function randomPositiveIntegersProvider(): array
    {
        $faker = Faker::create();
        $cases = [];

        for ($i = 0; $i < 100; $i++) {
            $goal = $faker->numberBetween(1, 999999);
            $cases["goal_{$goal}_iteration_{$i}"] = [$goal];
        }

        return $cases;
    }

    // =========================================================================
    // Feature Tests: Monthly Goal Endpoint
    // Validates: Requirements 2.1, 2.2, 2.3, 2.4, 2.5, 2.6
    // =========================================================================

    /**
     * Test that a valid goal update returns JSON with the goal value.
     *
     * **Validates: Requirements 2.1**
     */
    public function test_valid_goal_update_returns_json_with_goal(): void
    {
        $user = User::factory()->create();
        $store = Store::factory()->active()->create(['owner_user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->patchJson("/api/v1/stores/{$store->id}/analytics/monthly-goal", ['goal' => 150]);

        $response->assertOk()
            ->assertJson(['goal' => 150]);

        $this->assertDatabaseHas('stores', [
            'id' => $store->id,
            'monthly_goal' => 150,
        ]);
    }

    /**
     * Test that invalid goal values return 422 Unprocessable Entity.
     *
     * **Validates: Requirements 2.2, 2.3**
     */
    public function test_invalid_goal_zero_returns_422(): void
    {
        $user = User::factory()->create();
        $store = Store::factory()->active()->create(['owner_user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->patchJson("/api/v1/stores/{$store->id}/analytics/monthly-goal", ['goal' => 0]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['goal']);
    }

    /**
     * Test that negative goal returns 422.
     *
     * **Validates: Requirements 2.2, 2.3**
     */
    public function test_invalid_goal_negative_returns_422(): void
    {
        $user = User::factory()->create();
        $store = Store::factory()->active()->create(['owner_user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->patchJson("/api/v1/stores/{$store->id}/analytics/monthly-goal", ['goal' => -5]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['goal']);
    }

    /**
     * Test that string goal returns 422.
     *
     * **Validates: Requirements 2.2, 2.3**
     */
    public function test_invalid_goal_string_returns_422(): void
    {
        $user = User::factory()->create();
        $store = Store::factory()->active()->create(['owner_user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->patchJson("/api/v1/stores/{$store->id}/analytics/monthly-goal", ['goal' => 'abc']);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['goal']);
    }

    /**
     * Test that null goal returns 422.
     *
     * **Validates: Requirements 2.2, 2.3**
     */
    public function test_invalid_goal_null_returns_422(): void
    {
        $user = User::factory()->create();
        $store = Store::factory()->active()->create(['owner_user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->patchJson("/api/v1/stores/{$store->id}/analytics/monthly-goal", ['goal' => null]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['goal']);
    }

    /**
     * Test that float goal returns 422.
     *
     * **Validates: Requirements 2.2, 2.3**
     */
    public function test_invalid_goal_float_returns_422(): void
    {
        $user = User::factory()->create();
        $store = Store::factory()->active()->create(['owner_user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->patchJson("/api/v1/stores/{$store->id}/analytics/monthly-goal", ['goal' => 3.14]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['goal']);
    }

    /**
     * Test that updating the goal invalidates the cached seller dashboard.
     *
     * **Validates: Requirements 2.4**
     */
    public function test_goal_update_invalidates_dashboard_cache(): void
    {
        $user = User::factory()->create();
        $store = Store::factory()->active()->create(['owner_user_id' => $user->id]);

        // Pre-populate cache for all period variants
        $periods = ['all', 'today', 'last_7_days', 'this_month', 'this_year'];
        foreach ($periods as $period) {
            Cache::put("seller_analytics:{$store->id}:{$period}", ['cached' => true], 900);
        }

        // Verify cache is set
        foreach ($periods as $period) {
            $this->assertTrue(Cache::has("seller_analytics:{$store->id}:{$period}"));
        }

        // Update the goal
        $this->actingAs($user, 'sanctum')
            ->patchJson("/api/v1/stores/{$store->id}/analytics/monthly-goal", ['goal' => 200]);

        // Verify cache is invalidated for all periods
        foreach ($periods as $period) {
            $this->assertFalse(
                Cache::has("seller_analytics:{$store->id}:{$period}"),
                "Cache for period '{$period}' should be invalidated after goal update"
            );
        }
    }

    /**
     * Test that when no goal is set, dashboard returns null goal and 0 achievement.
     *
     * **Validates: Requirements 2.5, 2.6**
     */
    public function test_null_goal_returns_zero_achievement_in_dashboard(): void
    {
        $user = User::factory()->create();
        $store = Store::factory()->active()->create([
            'owner_user_id' => $user->id,
            'monthly_goal' => null,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/stores/{$store->id}/analytics?period=this_month");

        $response->assertOk()
            ->assertJsonPath('monthly_goal.goal', null)
            ->assertJsonPath('monthly_goal.achievement_percent', 0);
    }

    /**
     * Test that user without a store gets 403.
     *
     * **Validates: Requirements 2.1 (authorization)**
     */
    public function test_user_without_store_gets_403(): void
    {
        $user = User::factory()->create();
        $store = Store::factory()->create(); // store owned by someone else

        $response = $this->actingAs($user, 'sanctum')
            ->patchJson("/api/v1/stores/{$store->id}/analytics/monthly-goal", ['goal' => 100]);

        $response->assertForbidden();
    }

    /**
     * Test that unauthenticated request gets 401.
     *
     * **Validates: Requirements 2.1 (authentication)**
     */
    public function test_unauthenticated_request_gets_401(): void
    {
        $store = Store::factory()->create();

        $response = $this->patchJson("/api/v1/stores/{$store->id}/analytics/monthly-goal", ['goal' => 100]);

        $response->assertUnauthorized();
    }

    // =========================================================================
    // Property 6: Invalid Goal Rejection
    //
    // For any value that is not a positive integer (including zero, negative
    // numbers, floats, strings, null), the monthly goal endpoint SHALL return
    // a 422 status code.
    //
    // **Validates: Requirements 2.2, 2.3**
    // =========================================================================

    #[DataProvider('invalidGoalValuesProvider')]
    public function test_invalid_goal_rejection_property(mixed $invalidGoal): void
    {
        $user = User::factory()->create();
        $store = Store::factory()->active()->create(['owner_user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->patchJson("/api/v1/stores/{$store->id}/analytics/monthly-goal", ['goal' => $invalidGoal]);

        $response->assertUnprocessable();
    }

    /**
     * Data provider generating 50+ random non-positive-integer values.
     * Includes: zero, negative numbers, floats, strings, null, booleans, arrays.
     */
    public static function invalidGoalValuesProvider(): array
    {
        $faker = Faker::create();
        $cases = [];

        // Zero
        $cases['zero'] = [0];

        // Null
        $cases['null'] = [null];

        // Negative integers (15 random)
        for ($i = 0; $i < 15; $i++) {
            $val = $faker->numberBetween(-999999, -1);
            $cases["negative_{$i}_{$val}"] = [$val];
        }

        // Floats (10 random positive floats - not integers)
        for ($i = 0; $i < 10; $i++) {
            $val = $faker->randomFloat(2, 0.01, 999.99);
            // Ensure it's not a whole number
            if ($val == (int) $val) {
                $val += 0.5;
            }
            $cases["float_{$i}"] = [$val];
        }

        // Negative floats (5 random)
        for ($i = 0; $i < 5; $i++) {
            $val = $faker->randomFloat(2, -999.99, -0.01);
            $cases["negative_float_{$i}"] = [$val];
        }

        // Strings (10 random)
        for ($i = 0; $i < 10; $i++) {
            $val = $faker->word();
            $cases["string_{$i}_{$val}"] = [$val];
        }

        // Empty string
        $cases['empty_string'] = [''];

        // Boolean values
        $cases['boolean_false'] = [false];

        // Arrays
        $cases['array'] = [['not', 'an', 'integer']];

        // Very large negative number
        $cases['large_negative'] = [-PHP_INT_MAX];

        // Numeric strings that are not positive integers
        $cases['numeric_string_zero'] = ['0'];
        $cases['numeric_string_negative'] = ['-5'];
        $cases['numeric_string_float'] = ['3.14'];

        return $cases;
    }
}
