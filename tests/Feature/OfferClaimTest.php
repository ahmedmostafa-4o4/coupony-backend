<?php

namespace Tests\Feature;

use App\Domain\Notification\Models\Notification;
use App\Domain\Notification\Services\NotificationService;
use App\Domain\Product\Enums\ProductApprovalStatus;
use App\Domain\Product\Enums\ProductOfferTargetRole;
use App\Domain\Product\Enums\ProductOfferType;
use App\Domain\Product\Enums\ProductStatus;
use App\Domain\Product\Models\OfferClaim;
use App\Domain\Product\Models\Product;
use App\Domain\Product\Models\ProductOfferVariantTarget;
use App\Domain\Product\Models\ProductVariant;
use App\Domain\Store\Enums\StorePermission;
use App\Domain\Store\Models\Store;
use App\Domain\Store\Models\StoreEmployee;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OfferClaimTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'admin', 'guard_name' => 'sanctum']);
        Role::create(['name' => 'seller', 'guard_name' => 'sanctum']);
        Role::create(['name' => 'customer', 'guard_name' => 'sanctum']);
        Role::create(['name' => 'store_employee', 'guard_name' => 'sanctum']);
        config(['logging.default' => 'null']);
    }

    public function test_customer_can_create_fixed_claim_and_stock_is_not_deducted_until_redemption(): void
    {
        $seller = $this->seller();
        $customer = $this->customer();
        $store = $this->storeFor($seller);
        $employee = $this->employeeFor($store);
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

        $createResponse = $this->actingAs($customer, 'sanctum')
            ->postJson("/api/v1/products/{$product->id}/claims", [
                'variant_ids' => [$variant->id],
            ]);

        $createResponse->assertCreated()
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.offer_snapshot.offer.type', 'fixed')
            ->assertJsonPath('data.offer_snapshot.selected_variants.0.id', $variant->id);

        $claim = OfferClaim::query()->firstOrFail();

        $this->assertDatabaseHas('offer_claims', [
            'id' => $claim->id,
            'product_id' => $product->id,
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('product_variants', [
            'id' => $variant->id,
            'stock_qty' => 3,
            'redemption_count' => 0,
        ]);

        $redeemResponse = $this->actingAs($employee, 'sanctum')
            ->postJson("/api/v1/stores/{$store->id}/offer-claims/redeem", [
                'qr_code_token' => $claim->qr_code_token,
            ]);

        $redeemResponse->assertOk()
            ->assertJsonPath('data.status', 'redeemed')
            ->assertJsonPath('data.redeemed_by', $employee->id);

        $this->assertDatabaseHas('product_variants', [
            'id' => $variant->id,
            'stock_qty' => 2,
            'redemption_count' => 1,
        ]);
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'redemption_count' => 1,
        ]);
    }

    public function test_customer_can_create_percentage_claim_with_immutable_snapshot(): void
    {
        $seller = $this->seller();
        $customer = $this->customer();
        $store = $this->storeFor($seller);
        $employee = $this->employeeFor($store);
        $product = Product::factory()->active()->approved()->create(['store_id' => $store->id]);
        $product->offer()->update([
            'type' => ProductOfferType::PERCENTAGE,
            'fixed_amount' => null,
            'percentage_value' => 20,
            'max_discount' => 100,
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addHour(),
            'claim_expiration_minutes' => 45,
        ]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'is_active' => true,
            'inventory_mode' => 'tracked',
            'stock_qty' => 5,
        ]);

        $response = $this->actingAs($customer, 'sanctum')
            ->postJson("/api/v1/products/{$product->id}/claims", [
                'variant_ids' => [$variant->id],
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.offer_snapshot.offer.type', 'percentage')
            ->assertJsonPath('data.offer_snapshot.offer.percentage_value', '20.00')
            ->assertJsonPath('data.offer_snapshot.selected_variants.0.id', $variant->id);

        $product->offer()->update([
            'percentage_value' => 10,
        ]);

        $claim = OfferClaim::query()->firstOrFail();
        $this->assertSame('20.00', data_get($claim->offer_snapshot, 'offer.percentage_value'));
    }

    public function test_claim_creation_sends_notifications_to_customer_store_owner_and_employees(): void
    {
        $seller = $this->seller();
        $customer = $this->customer();
        $store = $this->storeFor($seller);
        $employee = $this->employeeFor($store);
        $product = Product::factory()->active()->approved()->create(['store_id' => $store->id]);
        $product->offer()->update([
            'type' => ProductOfferType::FIXED,
            'fixed_amount' => 25,
            'claim_expiration_minutes' => 30,
        ]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($customer, 'sanctum')
            ->postJson("/api/v1/products/{$product->id}/claims", [
                'variant_ids' => [$variant->id],
            ]);

        $response->assertCreated();

        $claimId = $response->json('data.id');

        $customerNotification = Notification::query()
            ->where('user_id', $customer->id)
            ->where('type', 'offer_claim_created')
            ->firstOrFail();

        $this->assertSame('Offer claimed successfully', $customerNotification->title);
        $this->assertSame('Your offer claim has been created successfully.', $customerNotification->message);
        $this->assertSame('in_app', $customerNotification->channel);
        $this->assertSame('sent', $customerNotification->status);
        $this->assertSame(OfferClaim::class, $customerNotification->reference_type);
        $this->assertSame($claimId, $customerNotification->reference_id);
        $this->assertSame($claimId, $customerNotification->data['claim_id']);
        $this->assertSame($product->id, $customerNotification->data['product_id']);
        $this->assertSame($store->id, $customerNotification->data['store_id']);
        $this->assertNotNull($customerNotification->data['expires_at']);

        foreach ([$seller, $employee] as $recipient) {
            $storeNotification = Notification::query()
                ->where('user_id', $recipient->id)
                ->where('type', 'new_offer_claim')
                ->firstOrFail();

            $this->assertSame('New offer claim', $storeNotification->title);
            $this->assertSame('A customer claimed an offer from your store.', $storeNotification->message);
            $this->assertSame('in_app', $storeNotification->channel);
            $this->assertSame('sent', $storeNotification->status);
            $this->assertSame(OfferClaim::class, $storeNotification->reference_type);
            $this->assertSame($claimId, $storeNotification->reference_id);
            $this->assertSame($claimId, $storeNotification->data['claim_id']);
            $this->assertSame($product->id, $storeNotification->data['product_id']);
            $this->assertSame($store->id, $storeNotification->data['store_id']);
            $this->assertSame($customer->id, $storeNotification->data['customer_id']);
        }

        $this->assertSame(2, Notification::query()->where('type', 'new_offer_claim')->count());
    }

    public function test_claim_creation_still_succeeds_when_notifications_fail(): void
    {
        $this->app->instance(NotificationService::class, new class extends NotificationService
        {
            public function send(
                User $user,
                string $type,
                string $title,
                string $message,
                string $channel = 'in_app',
                array $data = [],
                ?string $referenceType = null,
                ?string $referenceId = null
            ): Notification {
                throw new \RuntimeException('Notification transport unavailable.');
            }
        });

        $seller = $this->seller();
        $customer = $this->customer();
        $store = $this->storeFor($seller);
        $product = Product::factory()->active()->approved()->create(['store_id' => $store->id]);
        $product->offer()->update([
            'type' => ProductOfferType::FIXED,
            'fixed_amount' => 25,
        ]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($customer, 'sanctum')
            ->postJson("/api/v1/products/{$product->id}/claims", [
                'variant_ids' => [$variant->id],
            ]);

        $response->assertCreated();

        $this->assertDatabaseHas('offer_claims', [
            'id' => $response->json('data.id'),
            'user_id' => $customer->id,
            'store_id' => $store->id,
            'product_id' => $product->id,
            'status' => 'active',
        ]);
    }

    public function test_buy_x_get_y_claim_snapshots_selected_buy_and_reward_variants_and_redeems_all_stock(): void
    {
        $seller = $this->seller();
        $customer = $this->customer();
        $store = $this->storeFor($seller);
        $employee = $this->employeeFor($store);
        $product = Product::factory()->active()->approved()->create(['store_id' => $store->id]);
        $product->offer()->update([
            'type' => ProductOfferType::BUY_X_GET_Y,
            'fixed_amount' => null,
            'percentage_value' => null,
            'max_discount' => null,
            'buy_qty' => 2,
            'get_qty' => 1,
            'allow_mix_buy_variants' => true,
            'allow_mix_reward_variants' => false,
            'claim_expiration_minutes' => 60,
        ]);

        $buyVariantA = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'sku' => 'BUY-A',
            'is_active' => true,
            'inventory_mode' => 'tracked',
            'stock_qty' => 4,
        ]);
        $buyVariantB = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'sku' => 'BUY-B',
            'is_active' => true,
            'inventory_mode' => 'tracked',
            'stock_qty' => 3,
        ]);
        $rewardVariant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'sku' => 'REWARD-1',
            'is_active' => true,
            'inventory_mode' => 'tracked',
            'stock_qty' => 2,
        ]);

        ProductOfferVariantTarget::query()->create([
            'offer_id' => $product->offer->id,
            'variant_id' => $buyVariantA->id,
            'role' => ProductOfferTargetRole::BUY,
        ]);
        ProductOfferVariantTarget::query()->create([
            'offer_id' => $product->offer->id,
            'variant_id' => $buyVariantB->id,
            'role' => ProductOfferTargetRole::BUY,
        ]);
        ProductOfferVariantTarget::query()->create([
            'offer_id' => $product->offer->id,
            'variant_id' => $rewardVariant->id,
            'role' => ProductOfferTargetRole::REWARD,
        ]);

        $createResponse = $this->actingAs($customer, 'sanctum')
            ->postJson("/api/v1/products/{$product->id}/claims", [
                'buy_variant_ids' => [$buyVariantA->id, $buyVariantB->id],
                'reward_variant_ids' => [$rewardVariant->id],
            ]);

        $createResponse->assertCreated()
            ->assertJsonPath('data.offer_snapshot.offer.type', 'buy_x_get_y')
            ->assertJsonCount(2, 'data.offer_snapshot.selected_buy_variants')
            ->assertJsonCount(1, 'data.offer_snapshot.selected_reward_variants');

        $claim = OfferClaim::query()->firstOrFail();

        $redeemResponse = $this->actingAs($employee, 'sanctum')
            ->postJson("/api/v1/stores/{$store->id}/offer-claims/redeem", [
                'qr_code_token' => $claim->qr_code_token,
            ]);

        $redeemResponse->assertOk()
            ->assertJsonPath('data.status', 'redeemed');

        $this->assertDatabaseHas('product_variants', [
            'id' => $buyVariantA->id,
            'stock_qty' => 3,
            'redemption_count' => 1,
        ]);
        $this->assertDatabaseHas('product_variants', [
            'id' => $buyVariantB->id,
            'stock_qty' => 2,
            'redemption_count' => 1,
        ]);
        $this->assertDatabaseHas('product_variants', [
            'id' => $rewardVariant->id,
            'stock_qty' => 1,
            'redemption_count' => 1,
        ]);
    }

    public function test_claim_creation_rejects_offer_outside_validity_window(): void
    {
        $seller = $this->seller();
        $customer = $this->customer();
        $store = $this->storeFor($seller);
        $employee = $this->employeeFor($store);
        $product = Product::factory()->active()->approved()->create(['store_id' => $store->id]);
        $product->offer()->update([
            'type' => ProductOfferType::PERCENTAGE,
            'fixed_amount' => null,
            'percentage_value' => 15,
            'starts_at' => now()->addHour(),
            'ends_at' => now()->addDays(2),
        ]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($customer, 'sanctum')
            ->postJson("/api/v1/products/{$product->id}/claims", [
                'variant_ids' => [$variant->id],
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'This offer is not yet claimable.');
    }

    public function test_redeem_rejects_expired_and_already_redeemed_claims(): void
    {
        $seller = $this->seller();
        $customer = $this->customer();
        $store = $this->storeFor($seller);
        $employee = $this->employeeFor($store);
        $product = Product::factory()->active()->approved()->create(['store_id' => $store->id]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'is_active' => true,
            'inventory_mode' => 'tracked',
            'stock_qty' => 3,
        ]);

        $expiredClaim = OfferClaim::query()->create([
            'user_id' => $customer->id,
            'store_id' => $store->id,
            'product_id' => $product->id,
            'offer_id' => $product->offer->id,
            'claim_token' => 'expired-claim-token',
            'qr_code_token' => 'expired-qr-token',
            'offer_snapshot' => [
                'offer' => ['type' => 'fixed'],
                'selected_variants' => [['id' => $variant->id]],
            ],
            'expires_at' => now()->subMinute(),
        ]);

        $this->actingAs($employee, 'sanctum')
            ->postJson("/api/v1/stores/{$store->id}/offer-claims/redeem", [
                'qr_code_token' => $expiredClaim->qr_code_token,
            ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'This claim has expired.');

        $activeClaimResponse = $this->actingAs($customer, 'sanctum')
            ->postJson("/api/v1/products/{$product->id}/claims", [
                'variant_ids' => [$variant->id],
            ]);

        $claim = OfferClaim::query()
            ->where('id', $activeClaimResponse->json('data.id'))
            ->firstOrFail();

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
    }

    public function test_store_employee_can_view_claim_details_and_redemption_history_for_own_store(): void
    {
        $seller = $this->seller();
        $customer = $this->customer();
        $store = $this->storeFor($seller);
        $employee = $this->employeeFor($store);
        $product = Product::factory()->active()->approved()->create(['store_id' => $store->id]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'is_active' => true,
        ]);

        $claimResponse = $this->actingAs($customer, 'sanctum')
            ->postJson("/api/v1/products/{$product->id}/claims", [
                'variant_ids' => [$variant->id],
            ]);

        $claimId = $claimResponse->json('data.id');

        $this->actingAs($employee, 'sanctum')
            ->getJson("/api/v1/stores/{$store->id}/offer-claims")
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', $claimId);

        $this->actingAs($employee, 'sanctum')
            ->getJson("/api/v1/stores/{$store->id}/offer-claims/{$claimId}")
            ->assertOk()
            ->assertJsonPath('data.id', $claimId)
            ->assertJsonPath('data.store_id', $store->id);
    }

    public function test_redemption_fails_for_employee_of_different_store_and_allows_store_owner(): void
    {
        $seller = $this->seller();
        $otherSeller = $this->seller();
        $customer = $this->customer();
        $store = $this->storeFor($seller);
        $otherStore = $this->storeFor($otherSeller);
        $wrongEmployee = $this->employeeFor($otherStore);
        $product = Product::factory()->active()->approved()->create(['store_id' => $store->id]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'is_active' => true,
            'inventory_mode' => 'tracked',
            'stock_qty' => 2,
        ]);

        $claimResponse = $this->actingAs($customer, 'sanctum')
            ->postJson("/api/v1/products/{$product->id}/claims", [
                'variant_ids' => [$variant->id],
            ]);

        $qrCodeToken = $claimResponse->json('data.qr_code_token');

        $this->actingAs($wrongEmployee, 'sanctum')
            ->postJson("/api/v1/stores/{$store->id}/offer-claims/redeem", [
                'qr_code_token' => $qrCodeToken,
            ])
            ->assertForbidden();

        $this->actingAs($seller, 'sanctum')
            ->postJson("/api/v1/stores/{$store->id}/offer-claims/redeem", [
                'qr_code_token' => $qrCodeToken,
            ])
            ->assertOk();
    }

    public function test_store_employee_cannot_manage_products_for_store(): void
    {
        $seller = $this->seller();
        $store = $this->storeFor($seller);
        $employee = $this->employeeFor($store);

        $this->actingAs($employee, 'sanctum')
            ->postJson("/api/v1/stores/{$store->id}/products", [
                'title' => 'Blocked Product',
                'slug' => 'blocked-product',
                'currency' => 'EGP',
                'variants' => [[
                    'title' => 'Blocked Variant',
                    'sku' => 'BLOCKED-VAR',
                    'original_price' => 100,
                    'currency' => 'EGP',
                    'is_default' => true,
                    'is_active' => true,
                    'inventory_mode' => 'tracked',
                    'stock_qty' => 5,
                    'allow_backorder' => false,
                    'attributes' => [],
                ]],
                'offer' => ['type' => 'fixed', 'fixed_amount' => 10],
            ])
            ->assertForbidden();
    }

    private function seller(): User
    {
        $seller = User::factory()->create();
        $seller->assignRole('seller');

        return $seller;
    }

    private function customer(): User
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        return $customer;
    }

    private function employeeFor(Store $store): User
    {
        $employee = User::factory()->create();
        $employee->assignRole('store_employee');

        StoreEmployee::query()->create([
            'store_id' => $store->id,
            'user_id' => $employee->id,
            'permissions' => [
                StorePermission::CLAIMS_VIEW->value,
                StorePermission::CLAIMS_REDEEM->value,
            ],
        ]);

        return $employee;
    }

    private function storeFor(User $user): Store
    {
        return Store::factory()->create([
            'owner_user_id' => $user->id,
        ]);
    }
}
