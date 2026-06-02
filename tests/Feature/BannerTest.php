<?php

namespace Tests\Feature;

use App\Domain\Banner\Enums\BannerStatus;
use App\Domain\Banner\Models\Banner;
use App\Domain\Banner\Models\BannerClaim;
use App\Domain\Banner\Models\BannerShare;
use App\Domain\Banner\Services\BannerService;
use App\Domain\Product\Enums\ProductApprovalStatus;
use App\Domain\Product\Enums\ProductOfferStatus;
use App\Domain\Product\Enums\ProductStatus;
use App\Domain\Product\Models\Product;
use App\Domain\Product\Models\ProductOffer;
use App\Domain\Store\Models\Store;
use App\Domain\User\Models\Address;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BannerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'admin', 'guard_name' => 'sanctum']);
        Role::create(['name' => 'seller', 'guard_name' => 'sanctum']);
        Role::create(['name' => 'customer', 'guard_name' => 'sanctum']);

        Storage::fake('public');
        Cache::flush();
        config(['logging.default' => 'null']);
    }

    public function test_seller_can_submit_banner_request_with_offers_branches_terms_and_image(): void
    {
        $seller = $this->seller();
        $store = $this->storeFor($seller);
        $offer = $this->offerForStore($store);
        $branch = $this->branchForStore($store);

        $response = $this->actingAs($seller, 'sanctum')
            ->post("/api/v1/stores/{$store->id}/banners", $this->payload([
                'offer_ids' => [$offer->id],
                'address_ids' => [$branch->id],
            ]));

        $bannerId = $response->json('data.id');

        $response->assertCreated()
            ->assertJsonPath('data.status', BannerStatus::PENDING->value)
            ->assertJsonPath('data.is_active', false)
            ->assertJsonPath('data.discount_label', '30% OFF');

        $this->assertDatabaseHas('banners', [
            'id' => $bannerId,
            'store_id' => $store->id,
            'requested_by' => $seller->id,
            'status' => BannerStatus::PENDING->value,
        ]);
        $this->assertDatabaseHas('banner_offers', [
            'banner_id' => $bannerId,
            'offer_id' => $offer->id,
        ]);
        $this->assertDatabaseHas('banner_branches', [
            'banner_id' => $bannerId,
            'address_id' => $branch->id,
        ]);

        Storage::disk('public')->assertExists(Banner::query()->findOrFail($bannerId)->image_url);
    }

    public function test_seller_banner_validation_rejects_min_transaction_and_foreign_store_links(): void
    {
        $seller = $this->seller();
        $store = $this->storeFor($seller);
        $foreignStore = $this->storeFor($this->seller());
        $foreignOffer = $this->offerForStore($foreignStore);
        $foreignBranch = $this->branchForStore($foreignStore);

        $this->actingAs($seller, 'sanctum')
            ->withHeader('Accept', 'application/json')
            ->post("/api/v1/stores/{$store->id}/banners", $this->payload([
                'offer_ids' => [$foreignOffer->id],
                'address_ids' => [$foreignBranch->id],
                'min_transaction' => 100,
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['offer_ids', 'address_ids', 'min_transaction']);
    }

    public function test_admin_approval_approves_pending_linked_offer_and_makes_banner_public(): void
    {
        $seller = $this->seller();
        $admin = $this->admin();
        $store = $this->storeFor($seller);
        $product = Product::factory()->create([
            'store_id' => $store->id,
            'status' => ProductStatus::INACTIVE,
            'approval_status' => ProductApprovalStatus::PENDING,
        ]);
        $product->offer()->update(['status' => ProductOfferStatus::INACTIVE]);
        $branch = $this->branchForStore($store);

        $createResponse = $this->actingAs($seller, 'sanctum')
            ->post("/api/v1/stores/{$store->id}/banners", $this->payload([
                'offer_ids' => [$product->offer->id],
                'address_ids' => [$branch->id],
            ]));

        $bannerId = $createResponse->json('data.id');

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/admin/banners/{$bannerId}/approve", [
                'priority' => 2,
            ])
            ->assertOk()
            ->assertJsonPath('data.status', BannerStatus::APPROVED->value)
            ->assertJsonPath('data.is_active', true)
            ->assertJsonPath('data.priority', 2);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'status' => ProductStatus::ACTIVE->value,
            'approval_status' => ProductApprovalStatus::APPROVED->value,
            'approved_by' => $admin->id,
        ]);
        $this->assertDatabaseHas('product_offers', [
            'id' => $product->offer->id,
            'status' => ProductOfferStatus::ACTIVE->value,
        ]);

        $this->getJson('/api/v1/customer/banners')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $bannerId)
            ->assertJsonPath('data.0.offers.0.id', $product->offer->id);
    }

    public function test_customer_banners_exclude_ineligible_items_order_by_priority_and_limit_to_ten(): void
    {
        $seller = $this->seller();
        $store = $this->storeFor($seller);
        $offer = $this->offerForStore($store);
        $branch = $this->branchForStore($store);

        foreach (range(1, 11) as $priority) {
            $this->visibleBanner($store, $offer, $branch, $priority);
        }

        $this->visibleBanner($store, $offer, $branch, 0, ['end_time' => now()->subMinute()]);
        $this->visibleBanner($store, $offer, $branch, 0, ['is_active' => false]);
        Banner::factory()->create([
            'store_id' => $store->id,
            'requested_by' => $seller->id,
            'status' => BannerStatus::PENDING,
            'is_active' => true,
            'end_time' => now()->addDay(),
        ])->offers()->attach($offer->id);

        $response = $this->getJson('/api/v1/customer/banners');

        $response->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('data.0.priority', 1)
            ->assertJsonPath('data.9.priority', 10);
    }

    public function test_customer_can_like_favorite_share_and_create_grouped_banner_claim(): void
    {
        $customer = $this->customer();
        $store = $this->storeFor($this->seller());
        $offer = $this->offerForStore($store);
        $branch = $this->branchForStore($store);
        $banner = $this->visibleBanner($store, $offer, $branch, 1);

        $this->actingAs($customer, 'sanctum')
            ->postJson("/api/v1/customer/banners/{$banner->id}/likes")
            ->assertOk()
            ->assertJsonPath('data.likes_count', 1)
            ->assertJsonPath('data.is_liked', true);

        $this->actingAs($customer, 'sanctum')
            ->postJson("/api/v1/customer/banners/{$banner->id}/likes")
            ->assertOk()
            ->assertJsonPath('data.likes_count', 1);

        $this->actingAs($customer, 'sanctum')
            ->postJson("/api/v1/customer/banners/{$banner->id}/favorites")
            ->assertOk()
            ->assertJsonPath('data.is_favorited', true);

        $this->actingAs($customer, 'sanctum')
            ->postJson("/api/v1/customer/banners/{$banner->id}/shares", ['platform' => 'whatsapp'])
            ->assertCreated();

        $this->assertSame(1, BannerShare::query()->where('banner_id', $banner->id)->count());

        $this->actingAs($customer, 'sanctum')
            ->getJson("/api/v1/customer/banners/{$banner->id}")
            ->assertOk()
            ->assertJsonPath('data.likes_count', 1)
            ->assertJsonPath('data.is_liked', true)
            ->assertJsonPath('data.is_favorited', true)
            ->assertJsonPath('data.store.id', $store->id)
            ->assertJsonPath('data.branches.0.id', $branch->id)
            ->assertJsonPath('data.offers.0.id', $offer->id)
            ->assertJsonPath('data.terms_of_use', 'Valid for selected branches only.');

        $claimResponse = $this->actingAs($customer, 'sanctum')
            ->postJson("/api/v1/customer/banners/{$banner->id}/claims");

        $claimResponse->assertCreated()
            ->assertJsonPath('data.banner_id', $banner->id)
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.claim_snapshot.offers.0.id', $offer->id)
            ->assertJsonPath('data.claim_snapshot.branches.0.id', $branch->id);

        $this->assertSame(1, BannerClaim::query()->where('banner_id', $banner->id)->count());
    }

    public function test_banner_claim_returns_422_when_no_eligible_offers_remain(): void
    {
        $customer = $this->customer();
        $store = $this->storeFor($this->seller());
        $offer = $this->offerForStore($store);
        $offer->update(['ends_at' => now()->subMinute()]);
        $branch = $this->branchForStore($store);
        $banner = $this->visibleBanner($store, $offer, $branch, 1);

        $this->actingAs($customer, 'sanctum')
            ->postJson("/api/v1/customer/banners/{$banner->id}/claims")
            ->assertStatus(422)
            ->assertJsonPath('message', 'This banner is not available for claiming.');
    }

    public function test_admin_update_invalidates_customer_banner_cache(): void
    {
        $admin = $this->admin();
        $store = $this->storeFor($this->seller());
        $offer = $this->offerForStore($store);
        $branch = $this->branchForStore($store);
        $banner = $this->visibleBanner($store, $offer, $branch, 1);

        $this->getJson('/api/v1/customer/banners')->assertOk();
        $this->assertTrue(Cache::has(BannerService::CUSTOMER_BANNERS_CACHE_KEY));

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/admin/banners/{$banner->id}", ['is_active' => false])
            ->assertOk();

        $this->assertFalse(Cache::has(BannerService::CUSTOMER_BANNERS_CACHE_KEY));
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
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

    private function storeFor(User $user): Store
    {
        return Store::factory()->create([
            'owner_user_id' => $user->id,
        ]);
    }

    private function offerForStore(Store $store): ProductOffer
    {
        $product = Product::factory()->active()->approved()->create([
            'store_id' => $store->id,
            'status' => ProductStatus::ACTIVE,
            'approval_status' => ProductApprovalStatus::APPROVED,
        ]);

        return $product->offer()->firstOrFail();
    }

    private function branchForStore(Store $store): Address
    {
        $branch = Address::factory()->create();
        $store->addresses()->attach($branch->id, ['label' => 'branch']);

        return $branch;
    }

    private function visibleBanner(Store $store, ProductOffer $offer, Address $branch, int $priority, array $overrides = []): Banner
    {
        $banner = Banner::factory()->approved()->create(array_replace([
            'store_id' => $store->id,
            'requested_by' => $store->owner_user_id,
            'priority' => $priority,
            'end_time' => now()->addDays(2),
            'terms_of_use' => 'Valid for selected branches only.',
        ], $overrides));

        $banner->offers()->attach($offer->id);
        $banner->branches()->attach($branch->id);

        return $banner;
    }

    private function payload(array $overrides = []): array
    {
        return array_replace_recursive([
            'image' => UploadedFile::fake()->create('banner.jpg', 100, 'image/jpeg'),
            'discount_label' => '30% OFF',
            'date_range' => 'June 1 - June 30',
            'cta_label' => 'Claim now',
            'terms_of_use' => 'Valid for selected branches only.',
            'end_time' => now()->addDays(5)->toDateTimeString(),
            'offer_ids' => [],
            'address_ids' => [],
        ], $overrides);
    }
}
