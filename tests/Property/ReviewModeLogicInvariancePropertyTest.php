<?php

namespace Tests\Property;

use App\Domain\Store\Models\Store;
use App\Domain\Subscription\Enums\SubscriptionStatus;
use App\Domain\Subscription\Models\Subscription;
use App\Domain\Subscription\Models\SubscriptionPlan;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Feature: subscription-system, Property 22: Review mode logic invariance
 *
 * **Validates: Requirements 13.2**
 *
 * For any subscription operation (initiate payment, confirm payment, state transitions,
 * enforcement), the outcome must be identical regardless of whether is_review_mode
 * is true or false.
 */
class ReviewModeLogicInvariancePropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Subscription endpoints to test for review mode invariance.
     * We test GET endpoints that return subscription data, as POST endpoints
     * require valid payment sessions which are side-effect heavy.
     */
    private const ENDPOINTS = [
        ['GET', '/overview'],
        ['GET', '/status'],
        ['GET', '/plans'],
        ['GET', '/history'],
        ['GET', '/entitlements'],
    ];

    /**
     * Data provider that generates 100+ iterations with random endpoints
     * and subscription states.
     *
     * @return array<string, array{0: int, 1: string}>
     */
    public static function randomReviewModeProvider(): array
    {
        $faker = \Faker\Factory::create();
        $iterations = 105;
        $cases = [];

        $endpointCount = count(self::ENDPOINTS);
        $statuses = ['active', 'grace', 'degraded', 'trial'];

        for ($i = 0; $i < $iterations; $i++) {
            $endpointIndex = $faker->numberBetween(0, $endpointCount - 1);
            $subscriptionStatus = $faker->randomElement($statuses);

            $cases["iteration_{$i}_endpoint_{$endpointIndex}_{$subscriptionStatus}"] = [
                $endpointIndex,
                $subscriptionStatus,
            ];
        }

        return $cases;
    }

    /**
     * @dataProvider randomReviewModeProvider
     */
    public function test_subscription_operations_produce_identical_outcomes_regardless_of_review_mode(
        int $endpointIndex,
        string $subscriptionStatus
    ): void {
        // Arrange: Create store with owner and subscription
        $owner = User::factory()->create();
        $store = Store::factory()->create(['owner_user_id' => $owner->id]);
        $plan = SubscriptionPlan::factory()->create();

        Subscription::create([
            'store_id' => $store->id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::from($subscriptionStatus),
            'billing_cycle' => 'monthly',
            'current_period_start' => now()->subDays(15),
            'current_period_end' => now()->addDays(15),
        ]);

        Sanctum::actingAs($owner);

        [$method, $path] = self::ENDPOINTS[$endpointIndex];
        $url = "/api/v1/stores/{$store->id}/subscription{$path}";

        // Act 1: Request with review_mode = false
        config(['subscription.is_review_mode' => false]);

        $responseWithoutReviewMode = match ($method) {
            'GET' => $this->getJson($url),
            'POST' => $this->postJson($url, []),
        };

        $statusWithout = $responseWithoutReviewMode->getStatusCode();
        $dataWithout = $responseWithoutReviewMode->json();

        // Act 2: Request with review_mode = true
        config(['subscription.is_review_mode' => true]);

        $responseWithReviewMode = match ($method) {
            'GET' => $this->getJson($url),
            'POST' => $this->postJson($url, []),
        };

        $statusWith = $responseWithReviewMode->getStatusCode();
        $dataWith = $responseWithReviewMode->json();

        // Assert: HTTP status codes must be identical
        $this->assertEquals(
            $statusWithout,
            $statusWith,
            "HTTP status code differs between review_mode=false ({$statusWithout}) and review_mode=true ({$statusWith}) for endpoint {$path}."
        );

        // Assert: The success field must be identical
        $this->assertEquals(
            $dataWithout['success'] ?? null,
            $dataWith['success'] ?? null,
            "The 'success' field differs between review_mode=false and review_mode=true for endpoint {$path}."
        );

        // Assert: The data structure must be identical (excluding is_review_mode metadata field itself)
        // The plans endpoint includes is_review_mode in payment_config, which is expected to differ
        if ($path === '/plans') {
            // For plans endpoint, verify everything except the is_review_mode flag is identical
            $dataWithoutNormalized = $dataWithout;
            $dataWithNormalized = $dataWith;

            // Remove the is_review_mode field from comparison since it's expected to differ
            if (isset($dataWithoutNormalized['data'])) {
                $this->removeReviewModeField($dataWithoutNormalized['data']);
            }
            if (isset($dataWithNormalized['data'])) {
                $this->removeReviewModeField($dataWithNormalized['data']);
            }

            $this->assertEquals(
                $dataWithoutNormalized,
                $dataWithNormalized,
                "Response data (excluding is_review_mode metadata) differs between review modes for endpoint {$path}."
            );
        } else {
            // For all other endpoints, the entire response must be identical
            $this->assertEquals(
                $dataWithout,
                $dataWith,
                "Response data differs between review_mode=false and review_mode=true for endpoint {$path}."
            );
        }
    }

    /**
     * Recursively remove is_review_mode fields from data for comparison.
     */
    private function removeReviewModeField(array|string|null &$data): void
    {
        if (!is_array($data)) {
            return;
        }

        unset($data['is_review_mode']);

        if (isset($data['payment_config'])) {
            unset($data['payment_config']['is_review_mode']);
        }

        foreach ($data as &$value) {
            if (is_array($value)) {
                $this->removeReviewModeField($value);
            }
        }
    }
}
