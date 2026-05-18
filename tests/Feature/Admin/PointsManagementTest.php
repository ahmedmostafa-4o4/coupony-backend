<?php

namespace Tests\Feature\Admin;

use App\Domain\Points\Models\StorePoints;
use App\Domain\Store\Models\Store;
use App\Domain\User\Models\User;
use App\Domain\User\Models\UserPoints;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PointsManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $customer;

    private Store $store;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'admin', 'guard_name' => 'sanctum']);
        Role::create(['name' => 'customer', 'guard_name' => 'sanctum']);
        Role::create(['name' => 'seller', 'guard_name' => 'sanctum']);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->customer = User::factory()->create();
        $this->customer->assignRole('customer');

        $seller = User::factory()->create();
        $seller->assignRole('seller');
        $this->store = Store::factory()->create(['owner_user_id' => $seller->id]);
    }

    public function test_admin_can_add_user_points(): void
    {
        $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/v1/admin/users/{$this->customer->id}/points/add", [
                'points' => 25,
                'reason' => 'manual_bonus',
                'note' => 'Support adjustment',
            ])
            ->assertOk()
            ->assertJsonPath('data.current_balance', 25)
            ->assertJsonPath('data.lifetime_earned', 25);

        $this->assertDatabaseHas('user_point_transactions', [
            'user_id' => $this->customer->id,
            'admin_user_id' => $this->admin->id,
            'type' => 'adjustment',
            'points' => 25,
            'reason' => 'manual_bonus',
        ]);
    }

    public function test_admin_can_deduct_user_points(): void
    {
        UserPoints::query()->create([
            'user_id' => $this->customer->id,
            'current_balance' => 40,
            'lifetime_earned' => 40,
        ]);

        $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/v1/admin/users/{$this->customer->id}/points/deduct", [
                'points' => 15,
                'reason' => 'manual_deduction',
            ])
            ->assertOk()
            ->assertJsonPath('data.current_balance', 25)
            ->assertJsonPath('data.lifetime_spent', 15);

        $this->assertDatabaseHas('user_point_transactions', [
            'user_id' => $this->customer->id,
            'admin_user_id' => $this->admin->id,
            'type' => 'adjustment',
            'points' => 15,
            'reason' => 'manual_deduction',
        ]);
    }

    public function test_admin_can_set_user_points(): void
    {
        $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/v1/admin/users/{$this->customer->id}/points/set", [
                'points' => 100,
                'reason' => 'admin_set',
            ])
            ->assertOk()
            ->assertJsonPath('data.current_balance', 100);

        $this->assertDatabaseHas('user_point_transactions', [
            'user_id' => $this->customer->id,
            'admin_user_id' => $this->admin->id,
            'type' => 'set',
            'points' => 100,
            'balance_before' => 0,
            'balance_after' => 100,
        ]);
    }

    public function test_admin_can_view_user_transactions(): void
    {
        $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/v1/admin/users/{$this->customer->id}/points/add", [
                'points' => 10,
                'reason' => 'manual_bonus',
            ])
            ->assertOk();

        $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/v1/admin/users/{$this->customer->id}/points/transactions")
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.reason', 'manual_bonus');
    }

    public function test_non_admin_cannot_access_admin_points_endpoints(): void
    {
        $this->actingAs($this->customer, 'sanctum')
            ->postJson("/api/v1/admin/users/{$this->customer->id}/points/add", [
                'points' => 10,
                'reason' => 'manual_bonus',
            ])
            ->assertForbidden();

        $this->actingAs($this->customer, 'sanctum')
            ->getJson("/api/v1/admin/stores/{$this->store->id}/points")
            ->assertForbidden();
    }

    public function test_admin_can_add_deduct_and_set_store_points(): void
    {
        $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/v1/admin/stores/{$this->store->id}/points/add", [
                'points' => 50,
                'reason' => 'store_bonus',
            ])
            ->assertOk()
            ->assertJsonPath('data.current_balance', 50);

        $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/v1/admin/stores/{$this->store->id}/points/deduct", [
                'points' => 20,
                'reason' => 'store_penalty',
            ])
            ->assertOk()
            ->assertJsonPath('data.current_balance', 30)
            ->assertJsonPath('data.lifetime_spent', 20);

        $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/v1/admin/stores/{$this->store->id}/points/set", [
                'points' => 80,
                'reason' => 'store_set',
            ])
            ->assertOk()
            ->assertJsonPath('data.current_balance', 80);

        $this->assertSame(3, $this->store->pointTransactions()->count());
        $this->assertDatabaseHas('store_point_transactions', [
            'store_id' => $this->store->id,
            'admin_user_id' => $this->admin->id,
            'type' => 'adjustment',
            'points' => 50,
            'reason' => 'store_bonus',
        ]);
        $this->assertDatabaseHas('store_point_transactions', [
            'store_id' => $this->store->id,
            'admin_user_id' => $this->admin->id,
            'type' => 'adjustment',
            'points' => 20,
            'reason' => 'store_penalty',
        ]);
        $this->assertDatabaseHas('store_point_transactions', [
            'store_id' => $this->store->id,
            'admin_user_id' => $this->admin->id,
            'type' => 'set',
            'points' => 50,
            'reason' => 'store_set',
        ]);
        $this->assertSame(80, StorePoints::query()->where('store_id', $this->store->id)->value('current_balance'));
    }
}
