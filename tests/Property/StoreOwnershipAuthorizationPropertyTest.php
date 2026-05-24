<?php

namespace Tests\Property;

use App\Domain\Store\Models\Store;
use App\Domain\Subscription\Models\SubscriptionPlan;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Feature: subscription-system, Property 4: Store ownership authorization
 *
 * **Validates: Requirements 1.5, 4.3, 11.2**
 *
 * For any authenticated user and store where the user is not the store owner
 * (and has no management permission), all subscription endpoints must return
 * HTTP 403 Forbidden.
 */
class StoreOwnershipAuthorizationPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * All subscription endpoints with their HTTP methods.
     */
    private const SUBSCRIPTION_ENDPOINTS = [
        ['POST', '/initiate-payment'],
        ['POST', '/confirm-payment'],
        ['GET', '/overview'],
        ['GET', '/status'],
        ['GET', '/plans'],
        ['GET', '/history'],
        ['GET', '/entitlements'],
    ];

    /**
     * Data provider that generates 100+ iterations with random non-owner users
     * attempting to access random subscription endpoints.
     *
     * @return array<string, array{0: int}>
     */
    public static function randomNonOwnerAccessProvider(): array
    {
        $faker = \Faker\Factory::create();
        $iterations = 105;
        $cases = [];

        $endpointCount = count(self::SUBSCRIPTION_ENDPOINTS);

        for ($i = 0; $i < $iterations; $i++) {
            $endpointIndex = $faker->numberBetween(0, $endpointCount - 1);
            $cases["iteration_{$i}_endpoint_{$endpointIndex}"] = [$endpointIndex];
        }

        return $cases;
    }

    /**
     * @dataProvider randomNonOwnerAccessProvider
     */
    public function test_non_owner_user_receives_403_on_subscription_endpoints(
        int $endpointIndex
    ): void {
        // Arrange: Create a store owned by one user
        $owner = User::factory()->create();
        $store = Store::factory()->create(['owner_user_id' => $owner->id]);
        $plan = SubscriptionPlan::factory()->create();

        // Create a different user who does NOT own the store
        $nonOwner = User::factory()->create();

        // Ensure the non-owner is not the store owner
        $this->assertNotEquals($owner->id, $nonOwner->id);
        $this->assertNotEquals($store->owner_user_id, $nonOwner->id);

        // Act: Authenticate as the non-owner and hit the subscription endpoint
        Sanctum::actingAs($nonOwner);

        [$method, $path] = self::SUBSCRIPTION_ENDPOINTS[$endpointIndex];
        $url = "/api/v1/stores/{$store->id}/subscription{$path}";

        // Provide valid request body for POST endpoints so validation passes
        // and the Gate authorization check is reached
        $postBody = match ($path) {
            '/initiate-payment' => ['plan_id' => $plan->id, 'billing_cycle' => 'monthly'],
            '/confirm-payment' => ['session_id' => fake()->uuid()],
            default => [],
        };

        $response = match ($method) {
            'GET' => $this->getJson($url),
            'POST' => $this->postJson($url, $postBody),
        };

        // Assert: Must receive 403 Forbidden
        $response->assertStatus(403);
    }
}
