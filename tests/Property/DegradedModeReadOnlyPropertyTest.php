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
 * Feature: subscription-system, Property 17: Degraded mode read-only access
 *
 * **Validates: Requirements 10.6**
 *
 * For any store with subscription status degraded, GET requests to protected
 * endpoints must pass through, while POST/PUT/DELETE requests that would exceed
 * free-tier limits must be blocked with HTTP 403.
 */
class DegradedModeReadOnlyPropertyTest extends TestCase
{
    use RefreshDatabase;

    private CheckSubscription $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = app(CheckSubscription::class);
    }

    /**
     * Data provider for GET requests in degraded mode - should always pass through.
     *
     * @return array<int, array{0: string}>
     */
    public static function degradedGetRequestsProvider(): array
    {
        $faker = \Faker\Factory::create();
        $iterations = 50;
        $cases = [];

        $paths = ['products', 'employees', 'branches', 'settings', 'analytics', 'dashboard'];

        for ($i = 0; $i < $iterations; $i++) {
            $path = $faker->randomElement($paths);
            $cases["get_iteration_{$i}_{$path}"] = [$path];
        }

        return $cases;
    }

    /**
     * Data provider for write requests in degraded mode that exceed limits - should be blocked.
     *
     * @return array<int, array{0: string, 1: string, 2: int, 3: int}>
     */
    public static function degradedWriteBlockedProvider(): array
    {
        $faker = \Faker\Factory::create();
        $iterations = 55;
        $cases = [];

        $resourceTypes = ['products', 'employees', 'branches'];
        $writeMethods = ['POST', 'PUT', 'DELETE'];

        for ($i = 0; $i < $iterations; $i++) {
            $resourceType = $faker->randomElement($resourceTypes);
            $method = $faker->randomElement($writeMethods);
            // Plan limit between 1 and 5 (small for test performance)
            $planLimit = $faker->numberBetween(1, 5);
            // Usage at or over the limit
            $currentUsage = $faker->numberBetween($planLimit, $planLimit + 3);
            $cases["write_blocked_{$i}_{$resourceType}_{$method}_limit_{$planLimit}_usage_{$currentUsage}"] = [
                $resourceType,
                $method,
                $planLimit,
                $currentUsage,
            ];
        }

        return $cases;
    }

    /**
     * @dataProvider degradedGetRequestsProvider
     */
    public function test_degraded_mode_allows_get_requests(string $path): void
    {
        // Arrange
        $store = Store::factory()->create();
        $plan = SubscriptionPlan::factory()->create([
            'max_products' => 10,
            'max_employees' => 10,
            'max_branches' => 10,
        ]);

        Subscription::create([
            'store_id' => $store->id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::DEGRADED,
            'billing_cycle' => 'monthly',
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
        ]);

        $request = Request::create("/api/v1/stores/{$store->id}/{$path}", 'GET');
        $route = new Route('GET', "api/v1/stores/{store}/{$path}", fn () => null);
        $route->bind($request);
        $route->setParameter('store', $store);
        $request->setRouteResolver(fn () => $route);

        // Act
        $response = $this->middleware->handle(
            $request,
            fn ($req) => response()->json(['success' => true, 'data' => []]),
            null,
            null
        );

        // Assert - GET requests should pass through (200)
        $this->assertEquals(200, $response->getStatusCode(), "GET requests should pass through in degraded mode for path '{$path}'");

        $responseData = json_decode($response->getContent(), true);
        $this->assertTrue($responseData['success']);
    }

    /**
     * @dataProvider degradedWriteBlockedProvider
     */
    public function test_degraded_mode_blocks_write_requests_exceeding_limits(
        string $resourceType,
        string $method,
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
            'status' => SubscriptionStatus::DEGRADED,
            'billing_cycle' => 'monthly',
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
        ]);

        // Create resources to reach the usage count
        $this->createResourcesForStore($store, $resourceType, $currentUsage);

        $request = Request::create("/api/v1/stores/{$store->id}/{$resourceType}", $method);
        $route = new Route($method, "api/v1/stores/{store}/{$resourceType}", fn () => null);
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

        // Assert - Write requests exceeding limits should be blocked
        $this->assertEquals(403, $response->getStatusCode(), "Expected 403 for {$method} in degraded mode when {$resourceType} usage ({$currentUsage}) >= limit ({$planLimit})");

        $responseData = json_decode($response->getContent(), true);
        $this->assertFalse($responseData['success']);
        $this->assertEquals(
            'SUBSCRIPTION_LIMIT_REACHED',
            $responseData['error_code'],
            "Expected SUBSCRIPTION_LIMIT_REACHED for write in degraded mode exceeding limits"
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
