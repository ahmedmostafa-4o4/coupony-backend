<?php

namespace Tests\Feature;

use App\Domain\Points\Services\PointsService;
use App\Domain\Store\Models\Store;
use App\Domain\Store\Models\StoreEmployee;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PointReadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'admin', 'guard_name' => 'sanctum']);
        Role::create(['name' => 'seller', 'guard_name' => 'sanctum']);
        Role::create(['name' => 'customer', 'guard_name' => 'sanctum']);
        Role::create(['name' => 'store_employee', 'guard_name' => 'sanctum']);
    }

    public function test_store_employee_can_view_store_points_for_assigned_store(): void
    {
        $store = Store::factory()->create();
        $employee = User::factory()->create();
        $employee->assignRole('store_employee');
        StoreEmployee::query()->create([
            'store_id' => $store->id,
            'user_id' => $employee->id,
        ]);

        app(PointsService::class)->addStorePoints($store, 10, 'seed');

        $this->actingAs($employee, 'sanctum')
            ->getJson("/api/v1/stores/{$store->id}/points")
            ->assertOk()
            ->assertJsonPath('data.current_balance', 10);

        $this->actingAs($employee, 'sanctum')
            ->getJson("/api/v1/stores/{$store->id}/points/transactions")
            ->assertOk()
            ->assertJsonPath('meta.total', 1);
    }

    public function test_non_employee_cannot_view_store_points(): void
    {
        $store = Store::factory()->create();
        $user = User::factory()->create();
        $user->assignRole('customer');

        $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/stores/{$store->id}/points")
            ->assertForbidden();

        $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/stores/{$store->id}/points/transactions")
            ->assertForbidden();
    }
}
