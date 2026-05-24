<?php

namespace Tests\Unit;

use App\Domain\Store\Models\Store;
use App\Domain\Subscription\DTOs\EntitlementData;
use App\Domain\Subscription\Enums\BillingCycle;
use App\Domain\Subscription\Enums\SubscriptionStatus;
use App\Domain\Subscription\Models\Subscription;
use App\Domain\Subscription\Models\SubscriptionPlan;
use App\Domain\Subscription\Repositories\SubscriptionRepository;
use App\Domain\Subscription\Services\EntitlementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EntitlementServiceTest extends TestCase
{
    use RefreshDatabase;

    private EntitlementService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(EntitlementService::class);
    }

    public function test_get_entitlements_returns_zero_limits_when_no_subscription(): void
    {
        $store = Store::factory()->active()->create();

        $entitlements = $this->service->getEntitlements($store);

        $this->assertInstanceOf(EntitlementData::class, $entitlements);
        $this->assertEquals(0, $entitlements->limits['products']['limit']);
        $this->assertEquals(0, $entitlements->limits['employees']['limit']);
        $this->assertEquals(0, $entitlements->limits['branches']['limit']);
        $this->assertEmpty($entitlements->features);
    }

    public function test_get_entitlements_returns_plan_limits_and_usage(): void
    {
        $store = Store::factory()->active()->create();

        $plan = SubscriptionPlan::create([
            'name' => 'Premium',
            'slug' => 'premium',
            'price_monthly' => 99.00,
            'price_yearly' => 999.00,
            'currency' => 'EGP',
            'max_products' => 100,
            'max_employees' => 10,
            'max_branches' => 5,
            'features' => ['ai_assistant' => true, 'analytics' => true],
            'grace_period_days' => 7,
            'degraded_period_days' => 14,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        Subscription::create([
            'store_id' => $store->id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::ACTIVE,
            'billing_cycle' => BillingCycle::MONTHLY,
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
        ]);

        $entitlements = $this->service->getEntitlements($store);

        $this->assertInstanceOf(EntitlementData::class, $entitlements);
        $this->assertEquals(100, $entitlements->limits['products']['limit']);
        $this->assertEquals(0, $entitlements->limits['products']['usage']);
        $this->assertEquals(100, $entitlements->limits['products']['remaining']);
        $this->assertEquals(10, $entitlements->limits['employees']['limit']);
        $this->assertEquals(5, $entitlements->limits['branches']['limit']);
        $this->assertTrue($entitlements->features['ai_assistant']);
        $this->assertTrue($entitlements->features['analytics']);
    }

    public function test_check_limit_returns_true_when_under_limit(): void
    {
        $store = Store::factory()->active()->create();

        $plan = SubscriptionPlan::create([
            'name' => 'Basic',
            'slug' => 'basic',
            'price_monthly' => 49.00,
            'price_yearly' => 499.00,
            'currency' => 'EGP',
            'max_products' => 50,
            'max_employees' => 5,
            'max_branches' => 3,
            'features' => [],
            'grace_period_days' => 7,
            'degraded_period_days' => 14,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        Subscription::create([
            'store_id' => $store->id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::ACTIVE,
            'billing_cycle' => BillingCycle::MONTHLY,
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
        ]);

        $this->assertTrue($this->service->checkLimit($store, 'products'));
        $this->assertTrue($this->service->checkLimit($store, 'employees'));
        $this->assertTrue($this->service->checkLimit($store, 'branches'));
    }

    public function test_check_limit_returns_false_when_no_subscription(): void
    {
        $store = Store::factory()->active()->create();

        $this->assertFalse($this->service->checkLimit($store, 'products'));
        $this->assertFalse($this->service->checkLimit($store, 'employees'));
        $this->assertFalse($this->service->checkLimit($store, 'branches'));
    }

    public function test_check_limit_returns_false_for_unknown_resource_type(): void
    {
        $store = Store::factory()->active()->create();

        $plan = SubscriptionPlan::create([
            'name' => 'Basic',
            'slug' => 'basic-unknown',
            'price_monthly' => 49.00,
            'price_yearly' => 499.00,
            'currency' => 'EGP',
            'max_products' => 50,
            'max_employees' => 5,
            'max_branches' => 3,
            'features' => [],
            'grace_period_days' => 7,
            'degraded_period_days' => 14,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        Subscription::create([
            'store_id' => $store->id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::ACTIVE,
            'billing_cycle' => BillingCycle::MONTHLY,
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
        ]);

        $this->assertFalse($this->service->checkLimit($store, 'unknown_resource'));
    }

    public function test_check_feature_access_returns_true_when_feature_enabled(): void
    {
        $store = Store::factory()->active()->create();

        $plan = SubscriptionPlan::create([
            'name' => 'Premium',
            'slug' => 'premium-features',
            'price_monthly' => 99.00,
            'price_yearly' => 999.00,
            'currency' => 'EGP',
            'max_products' => 100,
            'max_employees' => 10,
            'max_branches' => 5,
            'features' => ['ai_assistant' => true, 'analytics' => true, 'export' => false],
            'grace_period_days' => 7,
            'degraded_period_days' => 14,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        Subscription::create([
            'store_id' => $store->id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::ACTIVE,
            'billing_cycle' => BillingCycle::MONTHLY,
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
        ]);

        $this->assertTrue($this->service->checkFeatureAccess($store, 'ai_assistant'));
        $this->assertTrue($this->service->checkFeatureAccess($store, 'analytics'));
        $this->assertFalse($this->service->checkFeatureAccess($store, 'export'));
        $this->assertFalse($this->service->checkFeatureAccess($store, 'nonexistent_feature'));
    }

    public function test_check_feature_access_returns_false_when_no_subscription(): void
    {
        $store = Store::factory()->active()->create();

        $this->assertFalse($this->service->checkFeatureAccess($store, 'ai_assistant'));
    }

    public function test_get_current_usage_returns_counts(): void
    {
        $store = Store::factory()->active()->create();

        $usage = $this->service->getCurrentUsage($store);

        $this->assertArrayHasKey('products', $usage);
        $this->assertArrayHasKey('employees', $usage);
        $this->assertArrayHasKey('branches', $usage);
        $this->assertEquals(0, $usage['products']);
        $this->assertEquals(0, $usage['employees']);
        $this->assertEquals(0, $usage['branches']);
    }

    public function test_get_entitlements_remaining_is_never_negative(): void
    {
        $store = Store::factory()->active()->create();

        $plan = SubscriptionPlan::create([
            'name' => 'Starter',
            'slug' => 'starter-negative',
            'price_monthly' => 29.00,
            'price_yearly' => 299.00,
            'currency' => 'EGP',
            'max_products' => 1,
            'max_employees' => 1,
            'max_branches' => 1,
            'features' => [],
            'grace_period_days' => 7,
            'degraded_period_days' => 14,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        Subscription::create([
            'store_id' => $store->id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::ACTIVE,
            'billing_cycle' => BillingCycle::MONTHLY,
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
        ]);

        // Even with 0 usage, remaining should be >= 0
        $entitlements = $this->service->getEntitlements($store);

        $this->assertGreaterThanOrEqual(0, $entitlements->limits['products']['remaining']);
        $this->assertGreaterThanOrEqual(0, $entitlements->limits['employees']['remaining']);
        $this->assertGreaterThanOrEqual(0, $entitlements->limits['branches']['remaining']);
    }

    public function test_entitlement_data_to_array(): void
    {
        $data = new EntitlementData(
            limits: [
                'products' => ['limit' => 10, 'usage' => 3, 'remaining' => 7],
            ],
            features: ['ai_assistant' => true],
        );

        $array = $data->toArray();

        $this->assertEquals(['limit' => 10, 'usage' => 3, 'remaining' => 7], $array['limits']['products']);
        $this->assertTrue($array['features']['ai_assistant']);
    }
}
