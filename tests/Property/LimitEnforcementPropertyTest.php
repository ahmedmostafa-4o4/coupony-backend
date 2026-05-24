<?php

namespace Tests\Property;

use App\Domain\Product\Models\Product;
use App\Domain\Store\Models\Store;
use App\Domain\Subscription\Enums\SubscriptionStatus;
use App\Domain\Subscription\Models\Subscription;
use App\Domain\Subscription\Models\SubscriptionPlan;
use App\Http\Middleware\CheckSubscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Tests\TestCase;

/**
 * Feature: subscription-system, Property 15: Limit enforcement
 *
 * **Validates: Requirements 10.4**
 *
 * For any store where the current usage count for a resource type equals or exceeds
 * the plan's limit for that resource type, attempting to create that resource must
 * return HTTP 403 with error code SUBSCRIPTION_LIMIT_REACHED.
 */
class LimitEnforcementPropertyTest extends TestCase
{
    use RefreshDatabase;

    private CheckSubscription $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = app(CheckSubscription::class);
    }

    /**
     * Data provider that generates 100+ random scenarios where a store is at or over
     * its plan limit for a resource type.
     *
     * @return array<int, array{0: string, 1: int, 2: int}>
     */
    public static function limitExceededProvider(): array
    {
        $faker = \Faker\Factory::create();
        $iterations = 105;
        $cases = [];

        $resourceTypes = ['products', 'employees', 'branches'];

        for ($i = 0; $i < $iterations; $i++) {
            $resourceType = $faker->randomElement($resourceTypes);
            // Plan limit between 1 and 10 (small for test performance)
            $planLimit = $faker->numberBetween(1, 10);
            // Usage at or over the limit
            $currentUsage = $faker->numberBetween($planLimit, $planLimit + 5);
            $cases["iteration_{$i}_{$resourceType}_limit_{$planLimit}_usage_{$currentUsage}"] = [
                $resourceType,
                $planLimit,
                $currentUsage,
            ];
        }

        return $cases;
    }

    /**
     * @dataProvider limitExceededProvider
     */
    public function test_at_or_over_limit_returns_403_subscription_limit_reached(
        string $resourceType,
        int $planLimit,
        int $currentUsage
    ): void {
        // Arrange
        $store = Store::factory()->create();

        $planData = [
            'max_products' => $resourceType === 'products' ? $planLimit : 1000,
            'max_employees' => $resourceType === 'employees' ? $planLimit : 1000,
            'max_branches' => $resourceType === 'branches' ? $planLimit : 1000,
        ];
        $plan = SubscriptionPlan::factory()->create($planData);

        Subscription::create([
            'store_id' => $store->id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::ACTIVE,
            'billing_cycle' => 'monthly',
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
        ]);

        // Create resources to reach the usage count
        $this->createResourcesForStore($store, $resourceType, $currentUsage);

        // Create a POST request (write operation) with the resource type parameter
        $request = Request::create("/api/v1/stores/{$store->id}/{$resourceType}", 'POST');
        $route = new Route('POST', "api/v1/stores/{store}/{$resourceType}", fn () => null);
        $route->bind($request);
        $route->setParameter('store', $store);
        $request->setRouteResolver(fn () => $route);

        // Act - pass resourceType to middleware
        $response = $this->middleware->handle(
            $request,
            fn ($req) => response()->json(['success' => true]),
            $resourceType,
            null
        );

        // Assert
        $this->assertEquals(403, $response->getStatusCode(), "Expected 403 when {$resourceType} usage ({$currentUsage}) >= limit ({$planLimit})");

        $responseData = json_decode($response->getContent(), true);
        $this->assertFalse($responseData['success']);
        $this->assertEquals(
            'SUBSCRIPTION_LIMIT_REACHED',
            $responseData['error_code'],
            "Expected SUBSCRIPTION_LIMIT_REACHED error code for {$resourceType} at/over limit"
        );
    }

    /**
     * Create the specified number of resources for a store.
     */
    private function createResourcesForStore(Store $store, string $resourceType, int $count): void
    {
        match ($resourceType) {
            'products' => Product::factory()->count($count)->create(['store_id' => $store->id]),
            'employees' => $this->createEmployees($store, $count),
            'branches' => $this->createBranches($store, $count),
        };
    }

    /**
     * Create employee records for the store.
     */
    private function createEmployees(Store $store, int $count): void
    {
        $users = \App\Domain\User\Models\User::factory()->count($count)->create();
        foreach ($users as $user) {
            $store->employees()->attach($user->id);
        }
    }

    /**
     * Create branch address records for the store.
     */
    private function createBranches(Store $store, int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            $address = \App\Domain\User\Models\Address::factory()->create();
            $store->addresses()->attach($address->id, ['label' => 'branch']);
        }
    }
}
