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
 * Feature: subscription-system, Property 16: Feature lock enforcement
 *
 * **Validates: Requirements 10.5**
 *
 * For any store whose active plan does not include a specific feature
 * (feature flag is false or absent), attempting to access that feature's
 * endpoint must return HTTP 403 with error code SUBSCRIPTION_FEATURE_LOCKED.
 */
class FeatureLockEnforcementPropertyTest extends TestCase
{
    use RefreshDatabase;

    private CheckSubscription $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = app(CheckSubscription::class);
    }

    /**
     * Data provider that generates 100+ random scenarios where a store's plan
     * does not include a requested feature.
     *
     * @return array<int, array{0: string, 1: array, 2: string}>
     */
    public static function featureLockedProvider(): array
    {
        $faker = \Faker\Factory::create();
        $iterations = 105;
        $cases = [];

        // Features that could be requested
        $allFeatures = ['ai_assistant', 'analytics', 'advanced_reports', 'bulk_import', 'api_access', 'custom_branding', 'priority_support'];
        $httpMethods = ['GET', 'POST', 'PUT', 'DELETE'];

        for ($i = 0; $i < $iterations; $i++) {
            // Pick a feature to request access to
            $requestedFeature = $faker->randomElement($allFeatures);

            // Create a plan features array that does NOT include the requested feature
            $planFeatures = [];
            $availableFeatures = array_diff($allFeatures, [$requestedFeature]);
            // Randomly include some other features
            $includedCount = $faker->numberBetween(0, count($availableFeatures));
            $includedFeatures = $faker->randomElements($availableFeatures, $includedCount);
            foreach ($includedFeatures as $f) {
                $planFeatures[$f] = true;
            }

            // Randomly decide if the requested feature is explicitly false or just absent
            if ($faker->boolean(50)) {
                $planFeatures[$requestedFeature] = false;
            }
            // Otherwise it's simply absent from the array

            $method = $faker->randomElement($httpMethods);

            $cases["iteration_{$i}_{$requestedFeature}_{$method}"] = [
                $requestedFeature,
                $planFeatures,
                $method,
            ];
        }

        return $cases;
    }

    /**
     * @dataProvider featureLockedProvider
     */
    public function test_feature_not_in_plan_returns_403_feature_locked(
        string $requestedFeature,
        array $planFeatures,
        string $method
    ): void {
        // Arrange
        $store = Store::factory()->create();

        $plan = SubscriptionPlan::factory()->create([
            'features' => $planFeatures,
            'max_products' => 1000,
            'max_employees' => 1000,
            'max_branches' => 1000,
        ]);

        Subscription::create([
            'store_id' => $store->id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::ACTIVE,
            'billing_cycle' => 'monthly',
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
        ]);

        $request = Request::create("/api/v1/stores/{$store->id}/feature/{$requestedFeature}", $method);
        $route = new Route($method, "api/v1/stores/{store}/feature/{feature}", fn () => null);
        $route->bind($request);
        $route->setParameter('store', $store);
        $request->setRouteResolver(fn () => $route);

        // Act - pass feature parameter to middleware
        $response = $this->middleware->handle(
            $request,
            fn ($req) => response()->json(['success' => true]),
            null,
            $requestedFeature
        );

        // Assert
        $this->assertEquals(403, $response->getStatusCode(), "Expected 403 when feature '{$requestedFeature}' is not in plan");

        $responseData = json_decode($response->getContent(), true);
        $this->assertFalse($responseData['success']);
        $this->assertEquals(
            'SUBSCRIPTION_FEATURE_LOCKED',
            $responseData['error_code'],
            "Expected SUBSCRIPTION_FEATURE_LOCKED error code when feature '{$requestedFeature}' is locked"
        );
    }
}
