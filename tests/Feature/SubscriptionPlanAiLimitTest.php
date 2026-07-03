<?php

namespace Tests\Feature;

use App\Domain\Store\Models\Store;
use App\Domain\Subscription\Models\SubscriptionPlan;
use App\Domain\User\Models\User;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SubscriptionPlanAiLimitTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'sanctum']);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
    }

    public function test_subscription_plan_seeder_assigns_daily_ai_limits(): void
    {
        $this->seed(SubscriptionPlanSeeder::class);

        $this->assertSame([
            'basic' => 15,
            'premium' => 30,
            'enterprise' => 60,
        ], SubscriptionPlan::query()
            ->orderBy('sort_order')
            ->pluck('max_ai_messages_per_day', 'slug')
            ->all());
    }

    public function test_plan_api_exposes_daily_ai_limit_as_an_entitlement(): void
    {
        $owner = User::factory()->create();
        $store = Store::factory()->create(['owner_user_id' => $owner->id]);
        SubscriptionPlan::factory()->create([
            'max_ai_messages_per_day' => 30,
            'sort_order' => 1,
        ]);

        $this->actingAs($owner, 'sanctum')
            ->getJson("/api/v1/stores/{$store->id}/subscription/plans")
            ->assertOk()
            ->assertJsonPath('data.0.entitlements.max_ai_messages_per_day', 30);
    }

    public function test_admin_can_create_and_update_daily_ai_limit(): void
    {
        $createResponse = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/admin/subscription-plans', $this->planPayload([
                'max_ai_messages_per_day' => 25,
            ]))
            ->assertCreated()
            ->assertJsonPath('data.entitlements.max_ai_messages_per_day', 25);

        $planId = $createResponse->json('data.id');

        $this->actingAs($this->admin, 'sanctum')
            ->patchJson("/api/v1/admin/subscription-plans/{$planId}", [
                'max_ai_messages_per_day' => 40,
            ])
            ->assertOk()
            ->assertJsonPath('data.entitlements.max_ai_messages_per_day', 40);

        $this->assertDatabaseHas('subscription_plans', [
            'slug' => 'ai-plan',
            'max_ai_messages_per_day' => 40,
            'is_active' => true,
        ]);
    }

    public function test_version_update_inherits_daily_ai_limit_when_omitted(): void
    {
        $plan = SubscriptionPlan::factory()->create([
            'slug' => 'inherited-plan',
            'max_ai_messages_per_day' => 35,
        ]);

        $this->actingAs($this->admin, 'sanctum')
            ->patchJson("/api/v1/admin/subscription-plans/{$plan->id}", [
                'price_monthly' => 250,
            ])
            ->assertOk()
            ->assertJsonPath('data.entitlements.max_ai_messages_per_day', 35);

        $this->assertDatabaseHas('subscription_plans', [
            'slug' => 'inherited-plan',
            'max_ai_messages_per_day' => 35,
            'is_active' => true,
        ]);
    }

    public function test_admin_cannot_create_or_update_a_negative_daily_ai_limit(): void
    {
        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/admin/subscription-plans', $this->planPayload([
                'slug' => 'invalid-ai-plan',
                'max_ai_messages_per_day' => -1,
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('max_ai_messages_per_day');

        $plan = SubscriptionPlan::factory()->create();

        $this->actingAs($this->admin, 'sanctum')
            ->patchJson("/api/v1/admin/subscription-plans/{$plan->id}", [
                'max_ai_messages_per_day' => -1,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('max_ai_messages_per_day');
    }

    private function planPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'AI Plan',
            'slug' => 'ai-plan',
            'description' => 'Plan with a daily AI message allowance.',
            'price_monthly' => 199,
            'price_yearly' => 1999,
            'currency' => 'EGP',
            'max_products' => 200,
            'max_employees' => 15,
            'max_branches' => 10,
            'features' => ['ai_assistant' => true],
            'grace_period_days' => 7,
            'degraded_period_days' => 14,
            'is_active' => true,
            'sort_order' => 2,
        ], $overrides);
    }
}
