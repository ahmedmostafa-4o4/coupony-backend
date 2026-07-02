<?php

namespace Tests\Feature;

use App\Domain\Store\Models\Store;
use App\Domain\Subscription\Enums\SubscriptionStatus;
use App\Domain\Subscription\Models\Subscription;
use App\Domain\Subscription\Models\SubscriptionPlan;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SubscriptionCancellationTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('cancellableStatusProvider')]
    public function test_store_owner_can_cancel_subscription_at_period_end(SubscriptionStatus $status): void
    {
        $owner = User::factory()->create();
        $store = Store::factory()->create(['owner_user_id' => $owner->id]);
        $plan = SubscriptionPlan::factory()->create();
        $periodEnd = now()->addDays(10);

        $subscription = Subscription::create([
            'store_id' => $store->id,
            'plan_id' => $plan->id,
            'status' => $status,
            'billing_cycle' => 'monthly',
            'current_period_start' => now()->subDays(20),
            'current_period_end' => $periodEnd,
            'trial_ends_at' => $periodEnd,
        ]);

        $response = $this->actingAs($owner, 'sanctum')
            ->postJson("/api/v1/stores/{$store->id}/subscription/cancel");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', $status->value);

        $subscription->refresh();
        $this->assertNotNull($subscription->cancelled_at);
        $this->assertEquals($status, $subscription->status);
        $this->assertSame($periodEnd->toDateTimeString(), $subscription->current_period_end->toDateTimeString());
    }

    #[DataProvider('nonCancellableStatusProvider')]
    public function test_store_owner_cannot_cancel_non_cancellable_subscription(SubscriptionStatus $status): void
    {
        $owner = User::factory()->create();
        $store = Store::factory()->create(['owner_user_id' => $owner->id]);
        $plan = SubscriptionPlan::factory()->create();

        $subscription = Subscription::create([
            'store_id' => $store->id,
            'plan_id' => $plan->id,
            'status' => $status,
            'billing_cycle' => 'monthly',
            'current_period_start' => now()->subDays(20),
            'current_period_end' => now()->addDays(10),
        ]);

        $this->actingAs($owner, 'sanctum')
            ->postJson("/api/v1/stores/{$store->id}/subscription/cancel")
            ->assertStatus(422);

        $subscription->refresh();
        $this->assertNull($subscription->cancelled_at);
        $this->assertEquals($status, $subscription->status);
    }

    public function test_store_owner_cannot_cancel_already_cancelled_subscription(): void
    {
        $owner = User::factory()->create();
        $store = Store::factory()->create(['owner_user_id' => $owner->id]);
        $plan = SubscriptionPlan::factory()->create();
        $cancelledAt = now()->subDay();

        $subscription = Subscription::create([
            'store_id' => $store->id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::ACTIVE,
            'billing_cycle' => 'monthly',
            'current_period_start' => now()->subDays(20),
            'current_period_end' => now()->addDays(10),
            'cancelled_at' => $cancelledAt,
        ]);

        $this->actingAs($owner, 'sanctum')
            ->postJson("/api/v1/stores/{$store->id}/subscription/cancel")
            ->assertStatus(422);

        $subscription->refresh();
        $this->assertSame($cancelledAt->toDateTimeString(), $subscription->cancelled_at->toDateTimeString());
        $this->assertEquals(SubscriptionStatus::ACTIVE, $subscription->status);
    }

    public function test_store_owner_cannot_cancel_missing_subscription(): void
    {
        $owner = User::factory()->create();
        $store = Store::factory()->create(['owner_user_id' => $owner->id]);

        $this->actingAs($owner, 'sanctum')
            ->postJson("/api/v1/stores/{$store->id}/subscription/cancel")
            ->assertStatus(422);
    }

    public static function nonCancellableStatusProvider(): array
    {
        return [
            'none' => [SubscriptionStatus::NONE],
            'suspended' => [SubscriptionStatus::SUSPENDED],
            'archived' => [SubscriptionStatus::ARCHIVED],
        ];
    }

    public static function cancellableStatusProvider(): array
    {
        return [
            'trial' => [SubscriptionStatus::TRIAL],
            'active' => [SubscriptionStatus::ACTIVE],
            'grace' => [SubscriptionStatus::GRACE],
            'degraded' => [SubscriptionStatus::DEGRADED],
        ];
    }
}
