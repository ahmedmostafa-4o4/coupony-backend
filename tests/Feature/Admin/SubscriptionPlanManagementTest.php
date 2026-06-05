<?php

namespace Tests\Feature\Admin;

use App\Domain\Subscription\Models\SubscriptionPlan;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionPlanManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'sanctum']);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
    }

    public function test_admin_can_create_a_subscription_plan()
    {
        $response = $this->actingAs($this->admin)->postJson('/api/v1/admin/subscription-plans', [
            'name' => 'Pro Plan',
            'slug' => 'pro-plan',
            'description' => 'The pro plan',
            'price_monthly' => 29.99,
            'price_yearly' => 299.99,
            'currency' => 'EGP',
            'max_products' => 100,
            'max_employees' => 5,
            'max_branches' => 2,
            'features' => ['feature1', 'feature2'],
            'grace_period_days' => 7,
            'degraded_period_days' => 14,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('subscription_plans', [
            'slug' => 'pro-plan',
            'price_monthly' => 29.99,
        ]);
    }

    public function test_updating_a_plan_creates_a_new_version_and_deactivates_the_old_one()
    {
        $plan = SubscriptionPlan::factory()->create([
            'slug' => 'basic',
            'price_monthly' => 10.00,
            'is_active' => true,
        ]);

        $oldPlanId = $plan->id;

        $response = $this->actingAs($this->admin)->patchJson("/api/v1/admin/subscription-plans/{$plan->id}", [
            'price_monthly' => 15.00,
            'max_products' => 50,
        ]);

        $response->assertStatus(200);

        // Check that the old plan is now inactive and has a modified slug
        $this->assertDatabaseHas('subscription_plans', [
            'id' => $oldPlanId,
            'is_active' => 0,
        ]);
        
        $oldPlan = SubscriptionPlan::find($oldPlanId);
        $this->assertStringContainsString('basic-legacy-', $oldPlan->slug);
        $this->assertEquals(10.00, $oldPlan->price_monthly); // old price maintained

        // Check that a new plan was created with the original slug and new price
        $this->assertDatabaseHas('subscription_plans', [
            'slug' => 'basic',
            'price_monthly' => 15.00,
            'max_products' => 50,
            'is_active' => 1,
        ]);
    }

    public function test_destroy_soft_deletes_by_setting_is_active_false()
    {
        $plan = SubscriptionPlan::factory()->create([
            'slug' => 'enterprise',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->deleteJson("/api/v1/admin/subscription-plans/{$plan->id}");

        $response->assertStatus(200);

        $this->assertDatabaseHas('subscription_plans', [
            'id' => $plan->id,
            'is_active' => 0,
        ]);
        
        $deletedPlan = SubscriptionPlan::find($plan->id);
        $this->assertStringContainsString('enterprise-deleted-', $deletedPlan->slug);
    }
}
