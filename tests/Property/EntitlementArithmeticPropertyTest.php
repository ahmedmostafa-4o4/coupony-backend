<?php

namespace Tests\Property;

use App\Domain\PonyAI\Services\AiMessageQuotaService;
use App\Domain\Product\Models\Product;
use App\Domain\Store\Models\Store;
use App\Domain\Subscription\Enums\BillingCycle;
use App\Domain\Subscription\Enums\SubscriptionStatus;
use App\Domain\Subscription\Models\Subscription;
use App\Domain\Subscription\Models\SubscriptionPlan;
use App\Domain\Subscription\Services\EntitlementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Feature: subscription-system, Property 18: Entitlement arithmetic invariant
 *
 * For any store with an active subscription, for each numeric entitlement,
 * the `remaining` value must equal `limit - current_usage`, and all three
 * values (limit, usage, remaining) must be non-negative.
 *
 * **Validates: Requirements 8.1**
 */
class EntitlementArithmeticPropertyTest extends TestCase
{
    use RefreshDatabase;

    private EntitlementService $entitlementService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->entitlementService = app(EntitlementService::class);
    }

    #[Test]
    #[DataProvider('entitlementArithmeticDataProvider')]
    public function remaining_equals_limit_minus_usage_and_all_values_are_non_negative(
        int $maxProducts,
        int $maxEmployees,
        int $maxBranches,
        int $maxAiMessages,
        int $productUsage,
        int $employeeUsage,
        int $branchUsage,
        int $aiMessageUsage,
    ): void {
        // Arrange: Create a plan with the given limits
        $plan = SubscriptionPlan::factory()->create([
            'max_products' => $maxProducts,
            'max_employees' => $maxEmployees,
            'max_branches' => $maxBranches,
            'max_ai_messages_per_day' => $maxAiMessages,
        ]);

        // Create a store with an active subscription
        $store = Store::factory()->create();

        Subscription::create([
            'store_id' => $store->id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::ACTIVE,
            'billing_cycle' => BillingCycle::MONTHLY,
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
        ]);

        // Create products for usage (up to productUsage count)
        if ($productUsage > 0) {
            Product::factory()->count($productUsage)->create([
                'store_id' => $store->id,
            ]);
        }

        // Create employees for usage
        if ($employeeUsage > 0) {
            $employees = \App\Domain\User\Models\User::factory()->count($employeeUsage)->create();
            $store->employees()->attach($employees->pluck('id'));
        }

        // Create branch addresses for usage
        if ($branchUsage > 0) {
            $addresses = \App\Domain\User\Models\Address::factory()->count($branchUsage)->create();
            foreach ($addresses as $address) {
                $store->addresses()->attach($address->id, ['label' => 'branch']);
            }
        }

        for ($message = 0; $message < $aiMessageUsage; $message++) {
            app(AiMessageQuotaService::class)->reserveStore($store);
        }

        // Act: Get entitlements
        $entitlements = $this->entitlementService->getEntitlements($store);

        $this->assertArrayHasKey('ai_messages', $entitlements->limits);

        // Assert: For each numeric entitlement, verify the arithmetic invariant
        foreach ($entitlements->limits as $resourceType => $values) {
            $limit = $values['limit'];
            $usage = $values['usage'];
            $remaining = $values['remaining'];

            // All values must be non-negative
            $this->assertGreaterThanOrEqual(
                0,
                $limit,
                "Limit for '{$resourceType}' must be non-negative, got {$limit}"
            );
            $this->assertGreaterThanOrEqual(
                0,
                $usage,
                "Usage for '{$resourceType}' must be non-negative, got {$usage}"
            );
            $this->assertGreaterThanOrEqual(
                0,
                $remaining,
                "Remaining for '{$resourceType}' must be non-negative, got {$remaining}"
            );

            // remaining = max(0, limit - usage)
            $expectedRemaining = max(0, $limit - $usage);
            $this->assertSame(
                $expectedRemaining,
                $remaining,
                "Remaining for '{$resourceType}' must equal max(0, limit - usage). "
                ."Expected: {$expectedRemaining}, Got: {$remaining} "
                ."(limit={$limit}, usage={$usage})"
            );
        }
    }

    /**
     * Data provider generating 100+ random iterations for property-based testing.
     *
     * @return \Generator<string, array{int, int, int, int, int, int, int, int}>
     */
    public static function entitlementArithmeticDataProvider(): \Generator
    {
        $faker = \Faker\Factory::create();

        for ($i = 0; $i < 100; $i++) {
            $maxProducts = $faker->numberBetween(1, 1000);
            $maxEmployees = $faker->numberBetween(1, 1000);
            $maxBranches = $faker->numberBetween(1, 1000);
            $maxAiMessages = $faker->numberBetween(1, 100);

            // Usage can be 0 to limit+10 (to test over-limit scenarios)
            $productUsage = $faker->numberBetween(0, min($maxProducts + 10, 50));
            $employeeUsage = $faker->numberBetween(0, min($maxEmployees + 10, 20));
            $branchUsage = $faker->numberBetween(0, min($maxBranches + 10, 15));
            $aiMessageUsage = $faker->numberBetween(0, min($maxAiMessages, 10));

            yield "iteration_{$i}_products({$maxProducts}/{$productUsage})_employees({$maxEmployees}/{$employeeUsage})_branches({$maxBranches}/{$branchUsage})_ai({$maxAiMessages}/{$aiMessageUsage})" => [
                $maxProducts,
                $maxEmployees,
                $maxBranches,
                $maxAiMessages,
                $productUsage,
                $employeeUsage,
                $branchUsage,
                $aiMessageUsage,
            ];
        }
    }
}
