<?php

namespace Tests\Feature\Admin;

use App\Domain\Notification\Models\Notification;
use App\Domain\Store\Enums\StoreStatus;
use App\Domain\Store\Models\Store;
use App\Domain\User\Models\User;
use App\Domain\User\Models\UserRoles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StoreManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $seller;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        Role::create(['name' => 'admin', 'guard_name' => 'sanctum']);
        Role::create(['name' => 'seller_pending', 'guard_name' => 'sanctum']);
        Role::create(['name' => 'seller', 'guard_name' => 'sanctum']);
        Role::create(['name' => 'customer', 'guard_name' => 'sanctum']);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->seller = User::factory()->create();
        $this->seller->assignRole('customer');
        $this->seller->assignRole('seller_pending');

        UserRoles::create([
            'user_id' => $this->seller->id,
            'role_id' => Role::where('name', 'seller_pending')->value('id'),
            'store_id' => null,
            'granted_at' => now(),
        ]);
    }

    public function test_admin_can_view_pending_stores()
    {
        Store::factory()->count(3)->create(['status' => StoreStatus::PENDING]);
        Store::factory()->count(2)->create(['status' => StoreStatus::ACTIVE]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/stores/pending');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_admin_can_view_all_stores_with_filters()
    {
        Store::factory()->count(2)->create(['status' => StoreStatus::PENDING]);
        Store::factory()->count(3)->create(['status' => StoreStatus::ACTIVE]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/stores?status=active');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_admin_can_view_single_store()
    {
        $store = Store::factory()->create();

        $response = $this->actingAs($this->admin)
            ->getJson("/api/v1/admin/stores/{$store->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $store->id);
    }

    public function test_admin_can_approve_pending_store()
    {
        $store = Store::factory()->create([
            'status' => StoreStatus::PENDING,
            'owner_user_id' => $this->seller->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson("/api/v1/admin/stores/{$store->id}/approve", [
                'notes' => 'All documents verified',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('stores', [
            'id' => $store->id,
            'status' => StoreStatus::ACTIVE->value,
            'approved_by' => $this->admin->id,
        ]);

        $this->seller->refresh();
        $this->assertTrue($this->seller->hasRole('seller'));
        $this->assertFalse($this->seller->hasRole('seller_pending'));
        $this->assertDatabaseMissing('user_roles', [
            'user_id' => $this->seller->id,
            'role_id' => Role::where('name', 'seller_pending')->value('id'),
            'store_id' => null,
        ]);
        $this->assertDatabaseHas('user_roles', [
            'user_id' => $this->seller->id,
            'role_id' => Role::where('name', 'seller')->value('id'),
            'store_id' => null,
        ]);
        $this->assertDatabaseHas('user_roles', [
            'user_id' => $this->seller->id,
            'role_id' => Role::where('name', 'seller')->value('id'),
            'store_id' => $store->id,
        ]);

        $notification = Notification::query()
            ->where('user_id', $this->seller->id)
            ->where('type', 'store_approved')
            ->where('channel', 'in_app')
            ->firstOrFail();

        $this->assertSame('تم قبول متجرك', $notification->title);
        $this->assertSame('مبروك! تم الموافقة على متجرك وأصبح نشطاً الآن.', $notification->message);
        $this->assertSame(Store::class, $notification->reference_type);
        $this->assertSame($store->id, $notification->reference_id);
        $this->assertSame($store->id, $notification->data['store_id']);
        $this->assertSame(StoreStatus::ACTIVE->value, $notification->data['status']);
        $this->assertNotNull($notification->data['approved_at']);
    }

    public function test_admin_can_reject_pending_store()
    {
        $store = Store::factory()->create([
            'status' => StoreStatus::PENDING,
            'owner_user_id' => $this->seller->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson("/api/v1/admin/stores/{$store->id}/reject", [
                'reason' => 'Invalid documents',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('stores', [
            'id' => $store->id,
            'status' => StoreStatus::REJECTED->value,
            'rejected_by' => $this->admin->id,
            'rejection_reason' => 'Invalid documents',
        ]);

        $notification = Notification::query()
            ->where('user_id', $this->seller->id)
            ->where('type', 'store_rejected')
            ->where('channel', 'in_app')
            ->firstOrFail();

        $this->assertSame('تم رفض متجرك', $notification->title);
        $this->assertStringContainsString('Invalid documents', $notification->message);
        $this->assertSame(Store::class, $notification->reference_type);
        $this->assertSame($store->id, $notification->reference_id);
        $this->assertSame($store->id, $notification->data['store_id']);
        $this->assertSame(StoreStatus::REJECTED->value, $notification->data['status']);
        $this->assertSame('Invalid documents', $notification->data['rejection_reason']);
    }

    public function test_cannot_approve_non_pending_store()
    {
        $store = Store::factory()->create([
            'status' => StoreStatus::ACTIVE,
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson("/api/v1/admin/stores/{$store->id}/approve");

        $response->assertStatus(400);
    }

    public function test_cannot_reject_non_pending_store()
    {
        $store = Store::factory()->create([
            'status' => StoreStatus::ACTIVE,
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson("/api/v1/admin/stores/{$store->id}/reject", [
                'reason' => 'Test reason',
            ]);

        $response->assertStatus(400);
    }

    public function test_admin_can_view_store_statistics()
    {
        Store::factory()->count(2)->create(['status' => StoreStatus::PENDING]);
        Store::factory()->count(3)->create(['status' => StoreStatus::ACTIVE]);
        Store::factory()->count(1)->create(['status' => StoreStatus::REJECTED]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/stores/statistics');

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'total' => 6,
                    'pending' => 2,
                    'active' => 3,
                    'rejected' => 1,
                ],
            ]);
    }

    public function test_non_admin_cannot_access_store_management()
    {
        $user = User::factory()->create();
        $user->assignRole('customer');

        $response = $this->actingAs($user)
            ->getJson('/api/v1/admin/stores/pending');

        $response->assertStatus(403);
    }

    public function test_reject_requires_reason()
    {
        $store = Store::factory()->create(['status' => StoreStatus::PENDING]);

        $response = $this->actingAs($this->admin)
            ->postJson("/api/v1/admin/stores/{$store->id}/reject", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['reason']);
    }
}
