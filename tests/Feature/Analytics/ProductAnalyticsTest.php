<?php

namespace Tests\Feature\Analytics;

use App\Domain\Product\Models\Product;
use App\Domain\Product\Models\ProductLike;
use App\Domain\Product\Models\ProductShare;
use App\Domain\Product\Models\ProductView;
use App\Domain\Store\Models\Store;
use App\Domain\Store\Models\StoreFollowers;
use App\Domain\User\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Feature tests for the Product Analytics endpoint.
 *
 * GET /api/v1/stores/{storeId}/analytics/products/{productId}
 *
 * **Validates: Requirements 8.1, 8.2, 8.3, 9.1, 9.4, 10.1, 10.3, 11.7, 12.1, 12.3, 12.4, 13.2**
 */
class ProductAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: 401 without token.
     * Validates: Requirement 12.1
     */
    public function test_unauthenticated_request_returns_401(): void
    {
        $store = Store::factory()->create();
        $product = Product::factory()->create(['store_id' => $store->id]);

        $response = $this->getJson("/api/v1/stores/{$store->id}/analytics/products/{$product->id}");

        $response->assertUnauthorized();
    }

    /**
     * Test: 403 when product doesn't belong to user's store.
     * Validates: Requirement 12.3
     */
    public function test_product_not_in_users_store_returns_403(): void
    {
        $user = User::factory()->create();
        $userStore = Store::factory()->create(['owner_user_id' => $user->id]);

        // Create a product belonging to a different store
        $otherStore = Store::factory()->create();
        $product = Product::factory()->create(['store_id' => $otherStore->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/stores/{$userStore->id}/analytics/products/{$product->id}");

        $response->assertForbidden();
    }

    /**
     * Test: 404 when product ID doesn't exist.
     * Validates: Requirement 12.4
     */
    public function test_nonexistent_product_returns_404(): void
    {
        $user = User::factory()->create();
        $store = Store::factory()->create(['owner_user_id' => $user->id]);

        $fakeProductId = 'non-existent-uuid-12345';

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/stores/{$store->id}/analytics/products/{$fakeProductId}");

        $response->assertNotFound();
    }

    /**
     * Test: Valid response structure with all keys present.
     * Validates: Requirements 8.1, 9.1, 10.1, 13.2
     */
    public function test_valid_response_structure_with_all_keys(): void
    {
        Cache::flush();

        $user = User::factory()->create();
        $store = Store::factory()->create(['owner_user_id' => $user->id]);
        $product = Product::factory()->create(['store_id' => $store->id]);

        // Seed some interaction data
        ProductView::create([
            'product_id' => $product->id,
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
        ]);
        ProductLike::create([
            'product_id' => $product->id,
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/stores/{$store->id}/analytics/products/{$product->id}");

        $response->assertOk();

        // Verify top-level keys
        $response->assertJsonStructure([
            'header' => ['views', 'likes', 'comments', 'saves'],
            'overview' => ['impressions', 'reached_accounts', 'profile_visits', 'new_followers', 'traffic_sources'],
            'engagement' => ['total_interactions', 'engagement_rate', 'trend', 'action_breakdown' => ['likes', 'comments', 'saves', 'shares']],
            'audience' => ['followers_percent', 'non_followers_percent', 'age_groups', 'gender_groups'],
        ]);

        // Verify header values are integers
        $data = $response->json();
        $this->assertIsInt($data['header']['views']);
        $this->assertIsInt($data['header']['likes']);
        $this->assertIsInt($data['header']['comments']);
        $this->assertIsInt($data['header']['saves']);

        // Verify engagement rate is numeric
        $this->assertIsNumeric($data['engagement']['engagement_rate']);

        // Verify audience percentages are numeric
        $this->assertIsNumeric($data['audience']['followers_percent']);
        $this->assertIsNumeric($data['audience']['non_followers_percent']);
    }

    /**
     * Test: Cache hit returns same data without recomputing.
     * Validates: Requirement 8.2
     */
    public function test_cache_hit_returns_same_data(): void
    {
        Cache::flush();

        $user = User::factory()->create();
        $store = Store::factory()->create(['owner_user_id' => $user->id]);
        $product = Product::factory()->create(['store_id' => $store->id]);

        // First request - populates cache
        $firstResponse = $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/stores/{$store->id}/analytics/products/{$product->id}");

        $firstResponse->assertOk();
        $firstData = $firstResponse->json();

        // Add more data after the first request (should not affect cached response)
        ProductView::create([
            'product_id' => $product->id,
            'user_id' => $user->id,
            'ip_address' => '192.168.1.1',
        ]);

        // Second request - should return cached data
        $secondResponse = $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/stores/{$store->id}/analytics/products/{$product->id}");

        $secondResponse->assertOk();
        $secondData = $secondResponse->json();

        // Cached response should be identical to first response
        $this->assertEquals($firstData, $secondData);
    }

    /**
     * Test: Zero-data response: all counts are 0, audience defaults to 50/50 split, 7 equal age groups.
     * Validates: Requirements 8.3, 9.4, 11.7, 13.2
     */
    public function test_zero_data_response(): void
    {
        Cache::flush();

        $user = User::factory()->create();
        $store = Store::factory()->create(['owner_user_id' => $user->id]);
        $product = Product::factory()->create(['store_id' => $store->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/stores/{$store->id}/analytics/products/{$product->id}");

        $response->assertOk();
        $data = $response->json();

        // Header: all counts should be 0
        $this->assertSame(0, $data['header']['views']);
        $this->assertSame(0, $data['header']['likes']);
        $this->assertSame(0, $data['header']['comments']);
        $this->assertSame(0, $data['header']['saves']);

        // Overview: all counts should be 0, traffic_sources empty
        $this->assertSame(0, $data['overview']['impressions']);
        $this->assertSame(0, $data['overview']['reached_accounts']);
        $this->assertSame(0, $data['overview']['profile_visits']);
        $this->assertSame(0, $data['overview']['new_followers']);
        $this->assertSame([], $data['overview']['traffic_sources']);

        // Engagement: total_interactions 0, engagement_rate 0.0
        $this->assertSame(0, $data['engagement']['total_interactions']);

        // Audience: 50/50 follower split
        $this->assertEquals(50.0, $data['audience']['followers_percent']);
        $this->assertEquals(50.0, $data['audience']['non_followers_percent']);

        // Audience: 7 age groups with equal distribution
        $this->assertCount(7, $data['audience']['age_groups']);

        $expectedRanges = ['13-17', '18-24', '25-34', '35-44', '45-54', '55-64', '65+'];
        $actualRanges = array_column($data['audience']['age_groups'], 'range');
        $this->assertSame($expectedRanges, $actualRanges);

        // All age group percentages should sum to 100.0 (with floating point tolerance)
        $agePercentageSum = array_sum(array_column($data['audience']['age_groups'], 'percentage'));
        $this->assertEqualsWithDelta(100.0, $agePercentageSum, 0.01);

        // Gender groups should be empty when no data
        $this->assertSame([], $data['audience']['gender_groups']);
    }

    /**
     * Test: Engagement rate is 0.0 when impressions are 0.
     * Validates: Requirement 10.3
     */
    public function test_engagement_rate_is_zero_when_no_impressions(): void
    {
        Cache::flush();

        $user = User::factory()->create();
        $store = Store::factory()->create(['owner_user_id' => $user->id]);
        $product = Product::factory()->create(['store_id' => $store->id]);

        // No views (impressions) exist for this product
        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/stores/{$store->id}/analytics/products/{$product->id}");

        $response->assertOk();
        $data = $response->json();

        $this->assertEquals(0.0, $data['engagement']['engagement_rate']);
    }

    /**
     * Test: Audience defaults: 50.0/50.0 follower split when no interactions.
     * Validates: Requirement 11.7
     */
    public function test_audience_defaults_when_no_interactions(): void
    {
        Cache::flush();

        $user = User::factory()->create();
        $store = Store::factory()->create(['owner_user_id' => $user->id]);
        $product = Product::factory()->create(['store_id' => $store->id]);

        // Create some followers for the store but no product interactions
        StoreFollowers::create([
            'store_id' => $store->id,
            'user_id' => User::factory()->create()->id,
            'followed_at' => now(),
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/stores/{$store->id}/analytics/products/{$product->id}");

        $response->assertOk();
        $data = $response->json();

        // When no one interacted with the product, defaults to 50/50
        $this->assertEquals(50.0, $data['audience']['followers_percent']);
        $this->assertEquals(50.0, $data['audience']['non_followers_percent']);
    }

    public function test_product_shares_are_included_in_action_breakdown_and_trend(): void
    {
        Cache::flush();
        Carbon::setTestNow('2026-07-01 12:00:00');
        $user = User::factory()->create();
        $store = Store::factory()->create(['owner_user_id' => $user->id]);
        $product = Product::factory()->create([
            'store_id' => $store->id,
            'created_at' => now()->subDays(3),
        ]);

        $share = ProductShare::create([
            'product_id' => $product->id,
            'user_id' => $user->id,
            'platform' => 'copy_link',
        ]);
        $share->forceFill([
            'created_at' => Carbon::parse('2026-06-30 10:00:00'),
            'updated_at' => Carbon::parse('2026-06-30 10:00:00'),
        ])->save();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/stores/{$store->id}/analytics/products/{$product->id}?period=last_7_days");

        $response->assertOk()
            ->assertJsonPath('engagement.total_interactions', 1)
            ->assertJsonPath('engagement.action_breakdown.shares', 1);

        $trendPoint = collect($response->json('engagement.trend'))
            ->firstWhere('date', '2026-06-30');

        $this->assertNotNull($trendPoint);
        $this->assertSame(1, $trendPoint['count']);
    }
}
