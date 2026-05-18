<?php

namespace Tests\Feature;

use App\Domain\Product\Enums\ProductApprovalStatus;
use App\Domain\Product\Enums\ProductOfferType;
use App\Domain\Product\Enums\ProductStatus;
use App\Domain\Product\Models\OfferClaim;
use App\Domain\Product\Models\Product;
use App\Domain\Product\Models\ProductVariant;
use App\Domain\Store\Models\Store;
use App\Domain\Store\Models\StoreEmployee;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OfferRedeemPointsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'admin', 'guard_name' => 'sanctum']);
        Role::create(['name' => 'seller', 'guard_name' => 'sanctum']);
        Role::create(['name' => 'customer', 'guard_name' => 'sanctum']);
        Role::create(['name' => 'store_employee', 'guard_name' => 'sanctum']);

        config([
            'logging.default' => 'null',
            'points.offer_redeemed_user' => 20,
            'points.offer_redeemed_store' => 10,
        ]);
    }

    public function test_successful_offer_redemption_awards_user_and_store_points(): void
    {
        [$customer, $store, $employee, $claim, $product] = $this->createRedeemableClaim();

        $this->actingAs($employee, 'sanctum')
            ->postJson("/api/v1/stores/{$store->id}/offer-claims/redeem", [
                'qr_code_token' => $claim->qr_code_token,
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'redeemed');

        $this->assertDatabaseHas('user_points', [
            'user_id' => $customer->id,
            'current_balance' => 20,
            'lifetime_earned' => 20,
        ]);
        $this->assertDatabaseHas('store_points', [
            'store_id' => $store->id,
            'current_balance' => 10,
            'lifetime_earned' => 10,
        ]);
        $this->assertDatabaseHas('user_point_transactions', [
            'user_id' => $customer->id,
            'store_id' => $store->id,
            'offer_claim_id' => $claim->id,
            'type' => 'earn',
            'points' => 20,
            'reason' => 'offer_redeemed',
        ]);
        $this->assertDatabaseHas('store_point_transactions', [
            'store_id' => $store->id,
            'user_id' => $customer->id,
            'offer_claim_id' => $claim->id,
            'type' => 'earn',
            'points' => 10,
            'reason' => 'offer_redeemed',
        ]);
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'redemption_count' => 1,
        ]);
    }

    public function test_duplicate_redeemed_claim_does_not_award_points_twice(): void
    {
        [$customer, $store, $employee, $claim] = $this->createRedeemableClaim();

        $this->actingAs($employee, 'sanctum')
            ->postJson("/api/v1/stores/{$store->id}/offer-claims/redeem", [
                'qr_code_token' => $claim->qr_code_token,
            ])
            ->assertOk();

        $this->actingAs($employee, 'sanctum')
            ->postJson("/api/v1/stores/{$store->id}/offer-claims/redeem", [
                'qr_code_token' => $claim->qr_code_token,
            ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'This claim has already been redeemed.');

        $this->assertDatabaseHas('user_points', [
            'user_id' => $customer->id,
            'current_balance' => 20,
            'lifetime_earned' => 20,
        ]);
        $this->assertDatabaseHas('store_points', [
            'store_id' => $store->id,
            'current_balance' => 10,
            'lifetime_earned' => 10,
        ]);
        $this->assertSame(1, $customer->pointTransactions()->where('offer_claim_id', $claim->id)->count());
        $this->assertSame(1, $store->pointTransactions()->where('offer_claim_id', $claim->id)->count());
    }

    private function createRedeemableClaim(): array
    {
        $seller = User::factory()->create();
        $seller->assignRole('seller');

        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $store = Store::factory()->create(['owner_user_id' => $seller->id]);

        $employee = User::factory()->create();
        $employee->assignRole('store_employee');
        StoreEmployee::query()->create([
            'store_id' => $store->id,
            'user_id' => $employee->id,
        ]);

        $product = Product::factory()->active()->approved()->create([
            'store_id' => $store->id,
            'status' => ProductStatus::ACTIVE,
            'approval_status' => ProductApprovalStatus::APPROVED,
        ]);
        $product->offer()->update([
            'type' => ProductOfferType::FIXED,
            'fixed_amount' => 25,
            'percentage_value' => null,
            'max_discount' => null,
            'buy_qty' => null,
            'get_qty' => null,
            'claim_expiration_minutes' => 30,
        ]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'is_active' => true,
            'inventory_mode' => 'tracked',
            'stock_qty' => 3,
            'redemption_count' => 0,
        ]);

        $this->actingAs($customer, 'sanctum')
            ->postJson("/api/v1/products/{$product->id}/claims", [
                'variant_ids' => [$variant->id],
            ])
            ->assertCreated();

        return [$customer, $store, $employee, OfferClaim::query()->firstOrFail(), $product];
    }
}
