<?php

namespace Tests\Feature\Admin;

use App\Domain\Product\Enums\OfferClaimStatus;
use App\Domain\Product\Models\OfferClaim;
use App\Domain\User\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminOfferClaimManagementTest extends TestCase
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

    public function test_admin_can_list_offer_claims()
    {
        for ($i = 0; $i < 3; $i++) {
            $product = \App\Domain\Product\Models\Product::factory()->create();
            OfferClaim::factory()->create([
                'product_id' => $product->id,
                'offer_id' => $product->offer->id,
            ]);
        }

        $response = $this->actingAs($this->admin)->getJson('/api/v1/admin/offer-claims');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_admin_can_filter_offer_claims_by_status()
    {
        $product1 = \App\Domain\Product\Models\Product::factory()->create();
        OfferClaim::factory()->create([
            'status' => OfferClaimStatus::ACTIVE,
            'product_id' => $product1->id,
            'offer_id' => $product1->offer->id,
        ]);

        $product2 = \App\Domain\Product\Models\Product::factory()->create();
        OfferClaim::factory()->create([
            'status' => OfferClaimStatus::REDEEMED,
            'product_id' => $product2->id,
            'offer_id' => $product2->offer->id,
        ]);

        $response = $this->actingAs($this->admin)->getJson('/api/v1/admin/offer-claims?status=redeemed');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'redeemed');
    }

    public function test_admin_can_view_specific_offer_claim()
    {
        $product = \App\Domain\Product\Models\Product::factory()->create();
        $claim = OfferClaim::factory()->create([
            'product_id' => $product->id,
            'offer_id' => $product->offer->id,
        ]);

        $response = $this->actingAs($this->admin)->getJson("/api/v1/admin/offer-claims/{$claim->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $claim->id);
    }

    public function test_admin_can_cancel_offer_claim()
    {
        $product = \App\Domain\Product\Models\Product::factory()->create();
        $claim = OfferClaim::factory()->create([
            'status' => OfferClaimStatus::ACTIVE,
            'product_id' => $product->id,
            'offer_id' => $product->offer->id,
        ]);

        $response = $this->actingAs($this->admin)->postJson("/api/v1/admin/offer-claims/{$claim->id}/cancel", [
            'reason' => 'Fraudulent activity suspected.'
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'cancelled')
            ->assertJsonPath('data.cancellation_reason', 'Fraudulent activity suspected.');

        $this->assertDatabaseHas('offer_claims', [
            'id' => $claim->id,
            'status' => OfferClaimStatus::CANCELLED->value,
            'cancellation_reason' => 'Fraudulent activity suspected.',
        ]);
    }

    public function test_customer_cannot_cancel_offer_claim()
    {
        $product = \App\Domain\Product\Models\Product::factory()->create();
        $claim = OfferClaim::factory()->create([
            'status' => OfferClaimStatus::ACTIVE,
            'product_id' => $product->id,
            'offer_id' => $product->offer->id,
        ]);

        $response = $this->actingAs($this->customer)->postJson("/api/v1/admin/offer-claims/{$claim->id}/cancel", [
            'reason' => 'test'
        ]);

        $response->assertStatus(403);
    }
}
