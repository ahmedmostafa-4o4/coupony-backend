<?php

namespace Tests\Property;

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
 * Feature: subscription-system, Property 14: Blocked status enforcement
 *
 * **Validates: Requirements 10.1, 10.2, 10.3**
 *
 * For any store with subscription status in [none, suspended, archived],
 * the enforcement middleware must return HTTP 403 with the corresponding
 * error code (SUBSCRIPTION_REQUIRED, STORE_SUSPENDED, or STORE_ARCHIVED respectively).
 */
class BlockedStatusEnforcementPropertyTest extends TestCase
{
    use RefreshDatabase;

    private CheckSubscription $middleware;

    /**
     * Blocked statuses and their expected error codes.
     */
    private const BLOCKED_STATUS_MAP = [
        'none' => 'SUBSCRIPTION_REQUIRED',
        'suspended' => 'STORE_SUSPENDED',
        'archived' => 'STORE_ARCHIVED',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = app(CheckSubscription::class);
    }

    /**
     * Data provider that generates 100+ random combinations of blocked statuses
     * and HTTP methods to verify enforcement.
     *
     * @return array<int, array{0: string, 1: string, 2: string}>
     */
    public static function blockedStatusProvider(): array
    {
        $faker = \Faker\Factory::create();
        $iterations = 105;
        $cases = [];

        $blockedStatuses = ['none', 'suspended', 'archived'];
        $httpMethods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];

        for ($i = 0; $i < $iterations; $i++) {
            $status = $faker->randomElement($blockedStatuses);
            $method = $faker->randomElement($httpMethods);
            $expectedErrorCode = self::BLOCKED_STATUS_MAP[$status];
            $cases["iteration_{$i}_{$status}_{$method}"] = [$status, $method, $expectedErrorCode];
        }

        return $cases;
    }

    /**
     * @dataProvider blockedStatusProvider
     */
    public function test_blocked_status_returns_403_with_correct_error_code(
        string $status,
        string $method,
        string $expectedErrorCode
    ): void {
        // Arrange
        $store = Store::factory()->create();
        $plan = SubscriptionPlan::factory()->create();

        // For 'none' status, we either don't create a subscription or create one with 'none'
        if ($status !== 'none') {
            Subscription::create([
                'store_id' => $store->id,
                'plan_id' => $plan->id,
                'status' => SubscriptionStatus::from($status),
                'billing_cycle' => 'monthly',
                'current_period_start' => now(),
                'current_period_end' => now()->addMonth(),
            ]);
        }
        // For 'none' status, no subscription record means status defaults to 'none'

        $request = Request::create("/api/v1/stores/{$store->id}/products", $method);
        $route = new Route($method, 'api/v1/stores/{store}/products', fn () => null);
        $route->bind($request);
        $route->setParameter('store', $store);
        $request->setRouteResolver(fn () => $route);

        // Act
        $response = $this->middleware->handle($request, fn ($req) => response()->json(['success' => true]), null, null);

        // Assert
        $this->assertEquals(403, $response->getStatusCode(), "Expected 403 for status '{$status}' with method '{$method}'");

        $responseData = json_decode($response->getContent(), true);
        $this->assertFalse($responseData['success']);
        $this->assertEquals(
            $expectedErrorCode,
            $responseData['error_code'],
            "Expected error code '{$expectedErrorCode}' for status '{$status}', got '{$responseData['error_code']}'"
        );
    }
}
