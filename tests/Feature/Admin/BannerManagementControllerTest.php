<?php

namespace Tests\Feature\Admin;

use App\Domain\Banner\Enums\BannerStatus;
use App\Domain\Banner\Events\BannerApproved;
use App\Domain\Banner\Events\BannerRejected;
use App\Domain\Banner\Models\Banner;
use App\Domain\Store\Models\Store;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Event;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BannerManagementControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private User $admin;
    private User $user;
    private Store $store;

    protected function setUp(): void
    {
        parent::setUp();
        
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'sanctum']);
        
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
        
        $this->user = User::factory()->create();
        $this->store = Store::factory()->create([
            'owner_user_id' => $this->user->id,
        ]);
    }

    public function test_admin_can_list_banners()
    {
        Banner::factory()->count(3)->create([
            'store_id' => $this->store->id,
            'status' => BannerStatus::PENDING,
            'discount_label' => 'Super Sale',
        ]);
        
        Banner::factory()->count(2)->create([
            'store_id' => $this->store->id,
            'status' => BannerStatus::APPROVED,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')->getJson('/api/v1/admin/banners');

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data');
            
        // Test filtering
        $response = $this->actingAs($this->admin, 'sanctum')->getJson('/api/v1/admin/banners?status=' . BannerStatus::PENDING->value);
        $response->assertStatus(200)->assertJsonCount(3, 'data');
        
        // Test search
        $response = $this->actingAs($this->admin, 'sanctum')->getJson('/api/v1/admin/banners?search=Super');
        $response->assertStatus(200)->assertJsonCount(3, 'data');
    }

    public function test_admin_can_approve_banner()
    {
        Event::fake([BannerApproved::class]);

        $banner = Banner::factory()->create([
            'store_id' => $this->store->id,
            'status' => BannerStatus::PENDING,
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')->postJson("/api/v1/admin/banners/{$banner->id}/approve", [
            'notes' => 'Looks good',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('banners', [
            'id' => $banner->id,
            'status' => BannerStatus::APPROVED->value,
            'is_active' => true,
            'approved_by' => $this->admin->id,
        ]);

        Event::assertDispatched(BannerApproved::class, function ($e) use ($banner) {
            return $e->banner->id === $banner->id;
        });
    }

    public function test_admin_can_reject_banner()
    {
        Event::fake([BannerRejected::class]);

        $banner = Banner::factory()->create([
            'store_id' => $this->store->id,
            'status' => BannerStatus::PENDING,
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')->postJson("/api/v1/admin/banners/{$banner->id}/reject", [
            'reason' => 'Inappropriate content',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('banners', [
            'id' => $banner->id,
            'status' => BannerStatus::REJECTED->value,
            'rejection_reason' => 'Inappropriate content',
        ]);

        Event::assertDispatched(BannerRejected::class, function ($e) use ($banner) {
            return $e->banner->id === $banner->id && $e->reason === 'Inappropriate content';
        });
    }

    public function test_admin_can_update_banner()
    {
        $banner = Banner::factory()->create([
            'store_id' => $this->store->id,
            'priority' => 100,
            'is_active' => false,
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')->putJson("/api/v1/admin/banners/{$banner->id}", [
            'priority' => 50,
            'is_active' => true,
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('banners', [
            'id' => $banner->id,
            'priority' => 50,
            'is_active' => true,
        ]);
    }

    public function test_admin_can_delete_banner()
    {
        $banner = Banner::factory()->create([
            'store_id' => $this->store->id,
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')->deleteJson("/api/v1/admin/banners/{$banner->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('banners', [
            'id' => $banner->id,
        ]);
    }

    public function test_regular_user_cannot_access_banner_management()
    {
        $response = $this->actingAs($this->user, 'sanctum')->getJson('/api/v1/admin/banners');
        $response->assertStatus(403);
    }
}
