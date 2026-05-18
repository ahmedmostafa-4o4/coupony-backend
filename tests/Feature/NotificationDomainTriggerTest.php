<?php

namespace Tests\Feature;

use App\Domain\Notification\Models\Notification;
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

class NotificationDomainTriggerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'seller', 'guard_name' => 'sanctum']);
        Role::create(['name' => 'customer', 'guard_name' => 'sanctum']);
        Role::create(['name' => 'store_employee', 'guard_name' => 'sanctum']);

        config([
            'logging.default' => 'null',
            'points.offer_redeemed_user' => 20,
            'points.offer_redeemed_store' => 10,
        ]);
    }

    public function test_offer_claim_creation_creates_customer_notification(): void
    {
        [$customer, $store, , , $product, $variant] = $this->redeemableScenario();

        $claimId = $this->actingAs($customer, 'sanctum')
            ->postJson("/api/v1/products/{$product->id}/claims", [
                'variant_ids' => [$variant->id],
            ])
            ->assertCreated()
            ->json('data.id');

        $notification = Notification::query()
            ->where('user_id', $customer->id)
            ->where('type', 'offer_claim_created')
            ->firstOrFail();

        $this->assertSame($claimId, $notification->data['claim_id']);
        $this->assertSame($product->id, $notification->data['product_id']);
        $this->assertSame($store->id, $notification->data['store_id']);
    }

    public function test_offer_claim_creation_creates_store_owner_notification(): void
    {
        [$customer, $store, , , $product, $variant] = $this->redeemableScenario();

        $claimId = $this->actingAs($customer, 'sanctum')
            ->postJson("/api/v1/products/{$product->id}/claims", [
                'variant_ids' => [$variant->id],
            ])
            ->assertCreated()
            ->json('data.id');

        $notification = Notification::query()
            ->where('user_id', $store->owner_user_id)
            ->where('type', 'new_offer_claim')
            ->firstOrFail();

        $this->assertSame($claimId, $notification->data['claim_id']);
        $this->assertSame($customer->id, $notification->data['customer_id']);
    }

    public function test_offer_redemption_creates_customer_notification(): void
    {
        [$customer, $store, $employee, $claim] = $this->createClaim();

        $this->actingAs($employee, 'sanctum')
            ->postJson("/api/v1/stores/{$store->id}/offer-claims/redeem", [
                'qr_code_token' => $claim->qr_code_token,
            ])
            ->assertOk();

        $notification = Notification::query()
            ->where('user_id', $customer->id)
            ->where('type', 'offer_redeemed')
            ->firstOrFail();

        $this->assertSame($claim->id, $notification->data['claim_id']);
        $this->assertNotNull($notification->data['redeemed_at']);
    }

    public function test_offer_redemption_creates_store_owner_notification(): void
    {
        [$customer, $store, $employee, $claim] = $this->createClaim();

        $this->actingAs($employee, 'sanctum')
            ->postJson("/api/v1/stores/{$store->id}/offer-claims/redeem", [
                'qr_code_token' => $claim->qr_code_token,
            ])
            ->assertOk();

        $notification = Notification::query()
            ->where('user_id', $store->owner_user_id)
            ->where('type', 'offer_redeemed_by_employee')
            ->firstOrFail();

        $this->assertSame($claim->id, $notification->data['claim_id']);
        $this->assertSame($customer->id, $notification->data['customer_id']);
        $this->assertSame($employee->id, $notification->data['redeemed_by']);
    }

    public function test_offer_redemption_creates_points_earned_notifications_when_points_are_awarded(): void
    {
        [$customer, $store, $employee, $claim] = $this->createClaim();

        $this->actingAs($employee, 'sanctum')
            ->postJson("/api/v1/stores/{$store->id}/offer-claims/redeem", [
                'qr_code_token' => $claim->qr_code_token,
            ])
            ->assertOk();

        $customerNotification = Notification::query()
            ->where('user_id', $customer->id)
            ->where('type', 'points_earned')
            ->firstOrFail();
        $storeNotification = Notification::query()
            ->where('user_id', $store->owner_user_id)
            ->where('type', 'seller_points_earned')
            ->firstOrFail();

        $this->assertSame(20, $customerNotification->data['points']);
        $this->assertSame('offer_redeemed', $customerNotification->data['reason']);
        $this->assertSame(10, $storeNotification->data['points']);
        $this->assertSame('offer_redeemed', $storeNotification->data['reason']);
    }

    private function createClaim(): array
    {
        [$customer, $store, $employee, , $product, $variant] = $this->redeemableScenario();

        $this->actingAs($customer, 'sanctum')
            ->postJson("/api/v1/products/{$product->id}/claims", [
                'variant_ids' => [$variant->id],
            ])
            ->assertCreated();

        return [$customer, $store, $employee, OfferClaim::query()->firstOrFail(), $product, $variant];
    }

    private function redeemableScenario(): array
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
            'claim_expiration_minutes' => 30,
        ]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'is_active' => true,
            'inventory_mode' => 'tracked',
            'stock_qty' => 3,
        ]);

        return [$customer, $store, $employee, null, $product, $variant];
    }
}
