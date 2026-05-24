<?php

namespace Tests\Feature\Analytics;

use App\Domain\Analytics\Actions\GetSellerDashboardAction;
use App\Domain\Product\Enums\OfferClaimStatus;
use App\Domain\Product\Enums\ProductOfferType;
use App\Domain\Product\Models\OfferClaim;
use App\Domain\Product\Models\Product;
use App\Domain\Product\Models\ProductView;
use App\Domain\Store\Models\Store;
use App\Domain\Store\Models\StoreFollowers;
use App\Domain\User\Models\User;
use Faker\Factory as Faker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Feature tests for the Seller Dashboard endpoint (GET /api/v1/stores/{storeId}/analytics).
 *
 * Includes:
 * - Property 4: Top Performing Offers Ordering and Limit
 * - Property 5: Invalid Period Rejection
 * - Property 7: Response Shape Consistency
 *
 * **Validates: Requirements 1.1, 1.2, 1.3, 1.4, 1.5, 12.1, 12.2, 13.1, 13.3**
 */
class SellerDashboardTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<int, array{int[]}>
     */
    public static function randomOfferSetsProvider(): array
    {
        $faker = Faker::create();
        $datasets = [];

        for ($i = 0; $i < 20; $i++) {
            $offerCount = $faker->numberBetween(0, 15);
            $usageCounts = [];

            for ($j = 0; $j < $offerCount; $j++) {
                // Use varying claim counts to test ordering property
                $usageCounts[] = $faker->numberBetween(1, 10);
            }

            $datasets[] = [$usageCounts];
        }

        return $datasets;
    }

    #[DataProvider('randomOfferSetsProvider')]
    public function test_top_performing_offers_are_sorted_by_usage_count_descending(array $usageCounts): void
    {
        Cache::flush();

        $user = User::factory()->create();
        $store = Store::factory()->create(['owner_user_id' => $user->id]);

        $this->seedOfferClaims($store, $user, $usageCounts);

        $action = new GetSellerDashboardAction();
        $result = $action->execute($store, 'all');

        $topOffers = $result['top_performing_offers'];

        // Property: result is always an array (even when empty)
        $this->assertIsArray($topOffers);

        // Property: sorted by usage_count in descending order
        for ($i = 1; $i < count($topOffers); $i++) {
            $this->assertGreaterThanOrEqual(
                $topOffers[$i]['usage_count'],
                $topOffers[$i - 1]['usage_count'],
                "Top offers not sorted in descending order: index " . ($i - 1) .
                " (usage_count={$topOffers[$i - 1]['usage_count']}) should be >= index {$i}" .
                " (usage_count={$topOffers[$i]['usage_count']})"
            );
        }
    }

    #[DataProvider('randomOfferSetsProvider')]
    public function test_top_performing_offers_contain_at_most_10_items(array $usageCounts): void
    {
        Cache::flush();

        $user = User::factory()->create();
        $store = Store::factory()->create(['owner_user_id' => $user->id]);

        $this->seedOfferClaims($store, $user, $usageCounts);

        $action = new GetSellerDashboardAction();
        $result = $action->execute($store, 'all');

        $topOffers = $result['top_performing_offers'];

        // Property: at most 10 items
        $this->assertLessThanOrEqual(
            10,
            count($topOffers),
            'Top performing offers should contain at most 10 items, got ' . count($topOffers)
        );
    }

    /**
     * Seed offer claims for the given store with the specified usage counts per offer.
     */
    private function seedOfferClaims(Store $store, User $user, array $usageCounts): void
    {
        foreach ($usageCounts as $index => $count) {
            $product = Product::factory()->create([
                'store_id' => $store->id,
                'title' => "Product {$index}",
            ]);

            // The ProductFactory auto-creates an offer via afterCreating,
            // so we update it with the desired label for identification.
            $offer = $product->offer;
            $offer->update([
                'type' => ProductOfferType::FIXED,
                'label' => "Offer {$index}",
            ]);

            for ($c = 0; $c < $count; $c++) {
                OfferClaim::create([
                    'user_id' => $user->id,
                    'store_id' => $store->id,
                    'product_id' => $product->id,
                    'offer_id' => $offer->id,
                    'status' => OfferClaimStatus::REDEEMED,
                    'claim_token' => "claim-{$index}-{$c}-" . uniqid(),
                    'qr_code_token' => "qr-{$index}-{$c}-" . uniqid(),
                    'offer_snapshot' => ['offer' => ['type' => 'fixed']],
                    'redeemed_at' => now()->subDays(rand(0, 5)),
                ]);
            }
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  Feature Tests: Authentication & Authorization
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Test that unauthenticated requests return 401.
     * Validates: Requirement 12.1
     */
    public function test_unauthenticated_request_returns_401(): void
    {
        $store = Store::factory()->create();

        $response = $this->getJson("/api/v1/stores/{$store->id}/analytics");

        $response->assertStatus(401);
    }

    /**
     * Test that a user without a store gets 403.
     * Validates: Requirement 12.2
     */
    public function test_user_without_store_returns_403(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;
        $store = Store::factory()->create(); // store owned by someone else

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson("/api/v1/stores/{$store->id}/analytics");

        $response->assertStatus(403);
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  Feature Tests: Valid Response Structure
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Test that a valid request returns the correct response structure.
     * Validates: Requirements 1.1, 13.1, 13.3
     */
    public function test_valid_request_returns_correct_response_structure(): void
    {
        Cache::flush();

        $user = User::factory()->create();
        $store = Store::factory()->create(['owner_user_id' => $user->id]);
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson("/api/v1/stores/{$store->id}/analytics");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'monthly_goal' => ['goal', 'current', 'achievement_percent'],
                'new_followers' => ['count', 'growth_percent'],
                'store_visits' => ['count', 'growth_percent'],
                'offer_distribution',
                'peak_redemption_times',
                'top_performing_offers',
            ]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  Feature Tests: Period Default
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Test that omitting the period parameter defaults to 'all'.
     * Validates: Requirement 1.2
     */
    public function test_period_defaults_to_all_when_omitted(): void
    {
        Cache::flush();

        $user = User::factory()->create();
        $store = Store::factory()->create(['owner_user_id' => $user->id]);
        $token = $user->createToken('test-token')->plainTextToken;

        // Make request without period parameter
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson("/api/v1/stores/{$store->id}/analytics");

        $response->assertStatus(200);

        // Make request with explicit 'all' period
        $responseAll = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson("/api/v1/stores/{$store->id}/analytics?period=all");

        $responseAll->assertStatus(200);

        // Both should return the same data
        $this->assertEquals($response->json(), $responseAll->json());
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  Feature Tests: Invalid Period Returns 422
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Test that an invalid period returns 422.
     * Validates: Requirement 1.3
     */
    public function test_invalid_period_returns_422(): void
    {
        $user = User::factory()->create();
        $store = Store::factory()->create(['owner_user_id' => $user->id]);
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson("/api/v1/stores/{$store->id}/analytics?period=invalid_period");

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['period']);
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  Feature Tests: Cache Hit Behavior
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Test that cached responses are returned without recomputing.
     * Validates: Requirements 1.4, 1.5
     */
    public function test_cache_hit_returns_cached_response(): void
    {
        Cache::flush();

        $user = User::factory()->create();
        $store = Store::factory()->create(['owner_user_id' => $user->id]);
        $token = $user->createToken('test-token')->plainTextToken;

        // First request - populates cache
        $response1 = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson("/api/v1/stores/{$store->id}/analytics?period=all");

        $response1->assertStatus(200);

        // Verify cache key exists
        $cacheKey = "seller_analytics:{$store->id}:all";
        $this->assertTrue(Cache::has($cacheKey));

        // Second request - should return cached data
        $response2 = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson("/api/v1/stores/{$store->id}/analytics?period=all");

        $response2->assertStatus(200);

        // Both responses should be identical
        $this->assertEquals($response1->json(), $response2->json());
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  Feature Tests: Zero-Data Response
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Test that a store with no data returns a complete response with zeros.
     * Validates: Requirements 13.1, 13.3
     */
    public function test_zero_data_store_returns_complete_response(): void
    {
        Cache::flush();

        $user = User::factory()->create();
        $store = Store::factory()->create(['owner_user_id' => $user->id]);
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson("/api/v1/stores/{$store->id}/analytics?period=today");

        $response->assertStatus(200);

        $data = $response->json();

        // monthly_goal should have null goal, 0 current, 0 achievement
        $this->assertNull($data['monthly_goal']['goal']);
        $this->assertEquals(0, $data['monthly_goal']['current']);
        $this->assertEquals(0.0, $data['monthly_goal']['achievement_percent']);

        // new_followers should be 0 with 0.0 growth
        $this->assertEquals(0, $data['new_followers']['count']);
        $this->assertEquals(0.0, $data['new_followers']['growth_percent']);

        // store_visits should be 0 with 0.0 growth
        $this->assertEquals(0, $data['store_visits']['count']);
        $this->assertEquals(0.0, $data['store_visits']['growth_percent']);

        // offer_distribution should be empty array
        $this->assertIsArray($data['offer_distribution']);
        $this->assertEmpty($data['offer_distribution']);

        // peak_redemption_times should have exactly 28 buckets
        $this->assertIsArray($data['peak_redemption_times']);
        $this->assertCount(28, $data['peak_redemption_times']);

        // All heatmap counts should be 0
        foreach ($data['peak_redemption_times'] as $bucket) {
            $this->assertEquals(0, $bucket['count']);
        }

        // top_performing_offers should be empty array
        $this->assertIsArray($data['top_performing_offers']);
        $this->assertEmpty($data['top_performing_offers']);
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  Property 5: Invalid Period Rejection
    //
    //  For any string that is not one of `all`, `today`, `last_7_days`,
    //  `this_month`, or `this_year`, the seller dashboard endpoint SHALL
    //  return a 422 status code.
    //
    //  **Validates: Requirements 1.3**
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * @return array<string, array{string}>
     */
    public static function invalidPeriodStringsProvider(): array
    {
        $faker = Faker::create();
        $validPeriods = ['all', 'today', 'last_7_days', 'this_month', 'this_year'];
        $datasets = [];

        for ($i = 0; $i < 55; $i++) {
            $invalidPeriod = $faker->unique()->lexify('??????');

            // Ensure it's not accidentally a valid period
            while (in_array($invalidPeriod, $validPeriods, true)) {
                $invalidPeriod = $faker->unique()->lexify('??????');
            }

            $datasets["invalid_period_{$i}_{$invalidPeriod}"] = [$invalidPeriod];
        }

        // Also add some edge cases
        $edgeCases = [
            'ALL', 'Today', 'LAST_7_DAYS', 'This_Month', 'THIS_YEAR',
            'last7days', 'last-7-days', 'this-month', 'this-year',
            'yesterday', 'last_30_days', 'last_month', 'last_year',
            '', ' ', 'null', '0', '-1', 'true', 'false',
        ];

        foreach ($edgeCases as $case) {
            $datasets["edge_case_{$case}"] = [$case];
        }

        return $datasets;
    }

    /**
     * Property 5: Invalid Period Rejection
     *
     * For any string that is not one of the valid periods, the endpoint SHALL return 422.
     *
     * **Validates: Requirements 1.3**
     */
    #[DataProvider('invalidPeriodStringsProvider')]
    public function test_invalid_period_string_returns_422(string $invalidPeriod): void
    {
        $user = User::factory()->create();
        $store = Store::factory()->create(['owner_user_id' => $user->id]);
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson("/api/v1/stores/{$store->id}/analytics?period=" . urlencode($invalidPeriod));

        $response->assertStatus(422);
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  Property 7: Response Shape Consistency
    //
    //  For any store (with or without data) and any valid period, the seller
    //  dashboard response SHALL contain all top-level keys with their expected
    //  nested structure, never returning an error for lack of data.
    //
    //  **Validates: Requirements 13.1, 13.3**
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * @return array<string, array{bool, bool, bool, string}>
     */
    public static function storesWithRandomDataPresenceProvider(): array
    {
        $faker = Faker::create();
        $validPeriods = ['all', 'today', 'last_7_days', 'this_month', 'this_year'];
        $datasets = [];

        for ($i = 0; $i < 25; $i++) {
            $hasFollowers = $faker->boolean();
            $hasVisits = $faker->boolean();
            $hasOffers = $faker->boolean();
            $period = $faker->randomElement($validPeriods);

            $datasets["store_{$i}_followers_{$hasFollowers}_visits_{$hasVisits}_offers_{$hasOffers}_period_{$period}"] = [
                $hasFollowers,
                $hasVisits,
                $hasOffers,
                $period,
            ];
        }

        return $datasets;
    }

    /**
     * Property 7: Response Shape Consistency
     *
     * For any store (with or without data) and any valid period, the response
     * SHALL contain all top-level keys with their expected nested structure.
     *
     * **Validates: Requirements 13.1, 13.3**
     */
    #[DataProvider('storesWithRandomDataPresenceProvider')]
    public function test_response_shape_is_consistent_regardless_of_data_presence(
        bool $hasFollowers,
        bool $hasVisits,
        bool $hasOffers,
        string $period,
    ): void {
        Cache::flush();

        $user = User::factory()->create();
        $store = Store::factory()->create(['owner_user_id' => $user->id]);
        $token = $user->createToken('test-token')->plainTextToken;

        // Seed data based on flags
        if ($hasFollowers) {
            $followerCount = rand(1, 5);
            for ($i = 0; $i < $followerCount; $i++) {
                StoreFollowers::create([
                    'store_id' => $store->id,
                    'user_id' => User::factory()->create()->id,
                    'notification_enabled' => true,
                    'followed_at' => now()->subDays(rand(0, 3)),
                ]);
            }
        }

        if ($hasVisits) {
            $visitCount = rand(1, 5);
            $product = Product::factory()->create(['store_id' => $store->id]);
            for ($i = 0; $i < $visitCount; $i++) {
                ProductView::create([
                    'product_id' => $product->id,
                    'user_id' => User::factory()->create()->id,
                    'created_at' => now()->subDays(rand(0, 3)),
                ]);
            }
        }

        if ($hasOffers) {
            $offerCount = rand(1, 3);
            for ($i = 0; $i < $offerCount; $i++) {
                $product = Product::factory()->create(['store_id' => $store->id]);
                $offer = $product->offer;
                $offer->update(['type' => ProductOfferType::FIXED]);

                OfferClaim::create([
                    'user_id' => $user->id,
                    'store_id' => $store->id,
                    'product_id' => $product->id,
                    'offer_id' => $offer->id,
                    'status' => OfferClaimStatus::REDEEMED,
                    'claim_token' => 'claim-shape-' . uniqid(),
                    'qr_code_token' => 'qr-shape-' . uniqid(),
                    'offer_snapshot' => ['offer' => ['type' => 'fixed']],
                    'redeemed_at' => now()->subDays(rand(0, 3)),
                ]);
            }
        }

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson("/api/v1/stores/{$store->id}/analytics?period={$period}");

        $response->assertStatus(200);

        $data = $response->json();

        // Assert all top-level keys exist
        $this->assertArrayHasKey('monthly_goal', $data);
        $this->assertArrayHasKey('new_followers', $data);
        $this->assertArrayHasKey('store_visits', $data);
        $this->assertArrayHasKey('offer_distribution', $data);
        $this->assertArrayHasKey('peak_redemption_times', $data);
        $this->assertArrayHasKey('top_performing_offers', $data);

        // Assert monthly_goal structure
        $this->assertArrayHasKey('goal', $data['monthly_goal']);
        $this->assertArrayHasKey('current', $data['monthly_goal']);
        $this->assertArrayHasKey('achievement_percent', $data['monthly_goal']);

        // Assert new_followers structure
        $this->assertArrayHasKey('count', $data['new_followers']);
        $this->assertArrayHasKey('growth_percent', $data['new_followers']);
        $this->assertIsInt($data['new_followers']['count']);
        $this->assertIsNumeric($data['new_followers']['growth_percent']);

        // Assert store_visits structure
        $this->assertArrayHasKey('count', $data['store_visits']);
        $this->assertArrayHasKey('growth_percent', $data['store_visits']);
        $this->assertIsInt($data['store_visits']['count']);
        $this->assertIsNumeric($data['store_visits']['growth_percent']);

        // Assert offer_distribution is an array
        $this->assertIsArray($data['offer_distribution']);
        foreach ($data['offer_distribution'] as $item) {
            $this->assertArrayHasKey('type', $item);
            $this->assertArrayHasKey('percentage', $item);
        }

        // Assert peak_redemption_times has exactly 28 buckets
        $this->assertIsArray($data['peak_redemption_times']);
        $this->assertCount(28, $data['peak_redemption_times']);
        foreach ($data['peak_redemption_times'] as $bucket) {
            $this->assertArrayHasKey('day', $bucket);
            $this->assertArrayHasKey('time_window', $bucket);
            $this->assertArrayHasKey('count', $bucket);
            $this->assertIsInt($bucket['count']);
            $this->assertGreaterThanOrEqual(0, $bucket['count']);
        }

        // Assert top_performing_offers is an array with correct item structure
        $this->assertIsArray($data['top_performing_offers']);
        foreach ($data['top_performing_offers'] as $offer) {
            $this->assertArrayHasKey('product_title', $offer);
            $this->assertArrayHasKey('offer_type', $offer);
            $this->assertArrayHasKey('offer_label', $offer);
            $this->assertArrayHasKey('usage_count', $offer);
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  Property 4: Top Performing Offers Ordering and Limit (existing tests)
    // ──────────────────────────────────────────────────────────────────────────
}
