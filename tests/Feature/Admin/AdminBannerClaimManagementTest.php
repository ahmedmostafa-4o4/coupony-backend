<?php

namespace Tests\Feature\Admin;

use App\Domain\Banner\Enums\BannerClaimStatus;
use App\Domain\Banner\Models\BannerClaim;
use App\Domain\User\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminBannerClaimManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->admin = User::factory()->create()->assignRole('admin');
        $this->customer = User::factory()->create()->assignRole('customer');
    }

    public function test_admin_can_list_banner_claims()
    {
        BannerClaim::factory()->count(3)->create();

        $response = $this->actingAs($this->admin)->getJson('/api/v1/admin/banner-claims');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_admin_can_filter_banner_claims_by_status()
    {
        BannerClaim::factory()->create(['status' => BannerClaimStatus::ACTIVE]);
        BannerClaim::factory()->create(['status' => BannerClaimStatus::REDEEMED]);

        $response = $this->actingAs($this->admin)->getJson('/api/v1/admin/banner-claims?status=redeemed');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'redeemed');
    }

    public function test_admin_can_view_specific_banner_claim()
    {
        $claim = BannerClaim::factory()->create();

        $response = $this->actingAs($this->admin)->getJson("/api/v1/admin/banner-claims/{$claim->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $claim->id);
    }

    public function test_admin_can_cancel_banner_claim()
    {
        $claim = BannerClaim::factory()->create(['status' => BannerClaimStatus::ACTIVE]);

        $response = $this->actingAs($this->admin)->postJson("/api/v1/admin/banner-claims/{$claim->id}/cancel", [
            'reason' => 'Invalid banner claim.'
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'cancelled')
            ->assertJsonPath('data.cancellation_reason', 'Invalid banner claim.');

        $this->assertDatabaseHas('banner_claims', [
            'id' => $claim->id,
            'status' => BannerClaimStatus::CANCELLED->value,
            'cancellation_reason' => 'Invalid banner claim.',
        ]);
    }

    public function test_customer_cannot_cancel_banner_claim()
    {
        $claim = BannerClaim::factory()->create(['status' => BannerClaimStatus::ACTIVE]);

        $response = $this->actingAs($this->customer)->postJson("/api/v1/admin/banner-claims/{$claim->id}/cancel", [
            'reason' => 'test'
        ]);

        $response->assertStatus(403);
    }
}
