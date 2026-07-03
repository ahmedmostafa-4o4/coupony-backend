<?php

namespace Tests\Integration;

use App\Domain\Product\Models\Product;
use App\Domain\Store\Models\Store;
use App\Domain\Subscription\Enums\SubscriptionStatus;
use App\Domain\Subscription\Models\Subscription;
use App\Domain\Subscription\Models\SubscriptionPlan;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Integration tests for the CheckSubscription enforcement middleware.
 *
 * Tests the middleware through actual HTTP requests to protected routes
 * (e.g., product creation at POST /api/v1/stores/{store}/products).
 *
 * **Validates: Requirements 10.1, 10.2, 10.3, 10.4, 10.5, 10.6**
 */
class SubscriptionEnforcementTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Store $store;

    private SubscriptionPlan $plan;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'sanctum']);

        $this->owner = User::factory()->create();
        $this->store = Store::factory()->create(['owner_user_id' => $this->owner->id]);
        $this->plan = SubscriptionPlan::factory()->create([
            'max_products' => 5,
            'max_employees' => 3,
            'max_branches' => 2,
            'features' => ['ai_assistant' => true, 'analytics' => true],
        ]);
    }

    // ─── Requirement 10.1: No subscription → SUBSCRIPTION_REQUIRED ───

    public function test_store_with_no_subscription_gets_403_subscription_required(): void
    {
        // No subscription record exists for this store

        $response = $this->actingAs($this->owner, 'sanctum')
            ->postJson("/api/v1/stores/{$this->store->id}/products", $this->validProductPayload());

        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'error_code' => 'SUBSCRIPTION_REQUIRED',
        ]);
    }

    // ─── Requirement 10.2: Suspended subscription → STORE_SUSPENDED ───

    public function test_store_with_suspended_subscription_gets_403_store_suspended(): void
    {
        $this->createSubscription(SubscriptionStatus::SUSPENDED);

        $response = $this->actingAs($this->owner, 'sanctum')
            ->postJson("/api/v1/stores/{$this->store->id}/products", $this->validProductPayload());

        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'error_code' => 'STORE_SUSPENDED',
        ]);
    }

    // ─── Requirement 10.3: Archived subscription → STORE_ARCHIVED ───

    public function test_store_with_archived_subscription_gets_403_store_archived(): void
    {
        $this->createSubscription(SubscriptionStatus::ARCHIVED);

        $response = $this->actingAs($this->owner, 'sanctum')
            ->postJson("/api/v1/stores/{$this->store->id}/products", $this->validProductPayload());

        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'error_code' => 'STORE_ARCHIVED',
        ]);
    }

    // ─── Requirement 10.4: At product limit → SUBSCRIPTION_LIMIT_REACHED ───

    public function test_store_at_product_limit_gets_403_subscription_limit_reached(): void
    {
        $this->createSubscription(SubscriptionStatus::ACTIVE);

        // Create products up to the limit (max_products = 5)
        Product::factory()->count(5)->create(['store_id' => $this->store->id]);

        $response = $this->actingAs($this->owner, 'sanctum')
            ->postJson("/api/v1/stores/{$this->store->id}/products", $this->validProductPayload());

        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'error_code' => 'SUBSCRIPTION_LIMIT_REACHED',
        ]);
    }

    // ─── Requirement 10.5: Feature locked → SUBSCRIPTION_FEATURE_LOCKED ───

    public function test_store_without_ai_assistant_feature_gets_403_feature_locked(): void
    {
        // Create a plan without ai_assistant feature
        $limitedPlan = SubscriptionPlan::factory()->create([
            'max_products' => 100,
            'max_employees' => 50,
            'max_branches' => 20,
            'features' => ['ai_assistant' => false, 'analytics' => true],
        ]);

        Subscription::create([
            'store_id' => $this->store->id,
            'plan_id' => $limitedPlan->id,
            'status' => SubscriptionStatus::ACTIVE,
            'billing_cycle' => 'monthly',
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
        ]);

        // The seller chat route uses middleware 'subscription:null,ai_assistant'
        $response = $this->actingAs($this->owner, 'sanctum')
            ->postJson("/api/v1/pony/stores/{$this->store->id}/chat", [
                'message' => 'Hello AI',
            ]);

        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'error_code' => 'SUBSCRIPTION_FEATURE_LOCKED',
        ]);
    }

    // ─── Requirement 10.6: Degraded mode → GET passes, POST blocked at limit ───

    public function test_degraded_store_allows_get_requests(): void
    {
        $this->createSubscription(SubscriptionStatus::DEGRADED);

        $response = $this->actingAs($this->owner, 'sanctum')
            ->getJson("/api/v1/stores/{$this->store->id}/products");

        // GET should pass through the subscription middleware (may return 200 or other non-403)
        $response->assertStatus(200);
    }

    public function test_degraded_store_blocks_post_when_at_limit(): void
    {
        $this->createSubscription(SubscriptionStatus::DEGRADED);

        // Create products up to the limit (max_products = 5)
        Product::factory()->count(5)->create(['store_id' => $this->store->id]);

        $response = $this->actingAs($this->owner, 'sanctum')
            ->postJson("/api/v1/stores/{$this->store->id}/products", $this->validProductPayload());

        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'error_code' => 'SUBSCRIPTION_LIMIT_REACHED',
        ]);
    }

    // ─── Helpers ───

    private function createSubscription(SubscriptionStatus $status): Subscription
    {
        return Subscription::create([
            'store_id' => $this->store->id,
            'plan_id' => $this->plan->id,
            'status' => $status,
            'billing_cycle' => 'monthly',
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
        ]);
    }

    /**
     * Minimal valid product payload that satisfies CreateProductRequest validation.
     * The middleware runs before validation, so this payload doesn't need to be perfect
     * for blocked-status tests, but it should be valid enough for limit/degraded tests.
     */
    private function validProductPayload(): array
    {
        return [
            'title' => 'Test Product',
            'currency' => 'EGP',
            'variants' => [
                [
                    'title' => 'Default Variant',
                    'original_price' => 100.00,
                    'currency' => 'EGP',
                ],
            ],
            'offer' => [
                'type' => 'fixed',
                'fixed_amount' => 10.00,
            ],
        ];
    }
}
