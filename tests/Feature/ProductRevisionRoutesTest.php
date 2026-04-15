<?php

namespace Tests\Feature;

use App\Domain\Product\Enums\ProductApprovalStatus;
use App\Domain\Product\Enums\ProductRevisionStatus;
use App\Domain\Product\Models\Product;
use App\Domain\Product\Models\ProductRevision;
use App\Domain\Store\Models\Store;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductRevisionRoutesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'admin', 'guard_name' => 'sanctum']);
        Role::create(['name' => 'seller', 'guard_name' => 'sanctum']);

        Storage::fake('public');
    }

    public function test_seller_can_list_and_view_product_revisions(): void
    {
        $seller = $this->seller();
        $store = $this->storeFor($seller);

        $createResponse = $this->actingAs($seller, 'sanctum')
            ->postJson("/api/v1/stores/{$store->id}/products", $this->payload());

        $productId = $createResponse->json('data.id');
        $revisionId = ProductRevision::query()->where('product_id', $productId)->value('id');

        $this->actingAs($seller, 'sanctum')
            ->getJson("/api/v1/stores/{$store->id}/products/{$productId}/revisions")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', ProductRevisionStatus::PENDING->value);

        $this->actingAs($seller, 'sanctum')
            ->getJson("/api/v1/stores/{$store->id}/products/{$productId}/revisions/{$revisionId}")
            ->assertOk()
            ->assertJsonPath('data.id', $revisionId)
            ->assertJsonPath('data.payload.product.title', 'Revision Product');
    }

    public function test_admin_can_list_pending_revisions_and_approve_one(): void
    {
        $seller = $this->seller();
        $admin = $this->admin();
        $store = $this->storeFor($seller);

        $createResponse = $this->actingAs($seller, 'sanctum')
            ->postJson("/api/v1/stores/{$store->id}/products", $this->payload());

        $productId = $createResponse->json('data.id');
        $revisionId = ProductRevision::query()->where('product_id', $productId)->value('id');

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/products/pending')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $revisionId);

        $approveResponse = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/admin/products/revisions/{$revisionId}/approve", [
                'notes' => 'Approved for publishing',
            ]);

        $approveResponse->assertOk()
            ->assertJsonPath('data.id', $productId)
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.approval_status', ProductApprovalStatus::APPROVED->value);

        $this->assertDatabaseHas('product_revisions', [
            'id' => $revisionId,
            'status' => ProductRevisionStatus::APPROVED->value,
        ]);
    }

    public function test_admin_can_reject_pending_revision_without_changing_live_product(): void
    {
        $seller = $this->seller();
        $admin = $this->admin();
        $store = $this->storeFor($seller);

        $createResponse = $this->actingAs($seller, 'sanctum')
            ->postJson("/api/v1/stores/{$store->id}/products", $this->payload());

        $productId = $createResponse->json('data.id');
        $revisionId = ProductRevision::query()->where('product_id', $productId)->value('id');

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/admin/products/revisions/{$revisionId}/reject", [
                'reason' => 'Offer details need changes',
                'notes' => 'Please revise and resubmit',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', ProductRevisionStatus::REJECTED->value)
            ->assertJsonPath('data.rejection_reason', 'Offer details need changes');

        $this->assertDatabaseHas('products', [
            'id' => $productId,
            'title' => 'Revision Product',
            'status' => 'inactive',
        ]);
    }

    public function test_seller_update_after_approval_creates_pending_revision_and_keeps_live_data(): void
    {
        $seller = $this->seller();
        $admin = $this->admin();
        $store = $this->storeFor($seller);

        $createResponse = $this->actingAs($seller, 'sanctum')
            ->postJson("/api/v1/stores/{$store->id}/products", $this->payload());

        $productId = $createResponse->json('data.id');
        $initialRevisionId = ProductRevision::query()->where('product_id', $productId)->value('id');

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/admin/products/revisions/{$initialRevisionId}/approve");

        $updateResponse = $this->actingAs($seller, 'sanctum')
            ->putJson("/api/v1/stores/{$store->id}/products/{$productId}", $this->payload([
                'title' => 'Pending Edited Title',
            ]));

        $updateResponse->assertOk()
            ->assertJsonPath('data.title', 'Revision Product')
            ->assertJsonPath('data.approval_status', ProductApprovalStatus::APPROVED->value)
            ->assertJsonPath('data.pending_revision.status', ProductRevisionStatus::PENDING->value)
            ->assertJsonPath('data.pending_revision.payload.product.title', 'Pending Edited Title');

        $this->actingAs($seller, 'sanctum')
            ->getJson("/api/v1/stores/{$store->id}/products/{$productId}")
            ->assertOk()
            ->assertJsonPath('data.title', 'Revision Product')
            ->assertJsonPath('data.pending_revision.payload.product.title', 'Pending Edited Title');

        $pendingRevision = ProductRevision::query()
            ->where('product_id', $productId)
            ->where('status', ProductRevisionStatus::PENDING)
            ->first();

        $this->assertNotNull($pendingRevision);
        $this->assertSame('Pending Edited Title', data_get($pendingRevision->payload, 'product.title'));
        $this->assertDatabaseHas('products', [
            'id' => $productId,
            'title' => 'Revision Product',
            'published_revision_no' => 1,
        ]);
    }

    private function seller(): User
    {
        $seller = User::factory()->create();
        $seller->assignRole('seller');

        return $seller;
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }

    private function storeFor(User $user): Store
    {
        return Store::factory()->create([
            'owner_user_id' => $user->id,
        ]);
    }

    private function payload(array $overrides = []): array
    {
        $payload = [
            'title' => 'Revision Product',
            'slug' => 'revision-product',
            'short_description' => 'Short description',
            'description' => 'Long description',
            'currency' => 'EGP',
            'sku' => 'SKU-REV-001',
            'is_featured' => false,
            'category_ids' => [],
            'images' => [
                [
                    'file' => UploadedFile::fake()->create('product.jpg', 100, 'image/jpeg'),
                    'sort_order' => 0,
                    'is_primary' => true,
                ],
            ],
            'variants' => [
                [
                    'title' => 'Red / XL',
                    'option_summary' => 'Color: Red, Size: XL',
                    'sku' => 'SKU-REV-RED-XL',
                    'barcode' => '123456789',
                    'original_price' => 110,
                    'currency' => 'EGP',
                    'sort_order' => 0,
                    'is_default' => true,
                    'is_active' => true,
                    'inventory_mode' => 'tracked',
                    'stock_qty' => 8,
                    'low_stock_threshold' => 2,
                    'allow_backorder' => false,
                    'attributes' => [
                        [
                            'attribute_name' => 'color',
                            'attribute_value' => 'red',
                            'sort_order' => 0,
                        ],
                    ],
                ],
            ],
            'offer' => [
                'type' => 'fixed',
                'status' => 'active',
                'label' => 'Launch discount',
                'claim_expiration_minutes' => 1440,
                'fixed_amount' => 15,
                'percentage_value' => null,
                'max_discount' => null,
                'buy_qty' => null,
                'get_qty' => null,
                'allow_mix_buy_variants' => false,
                'allow_mix_reward_variants' => false,
                'buy_variant_skus' => [],
                'reward_variant_skus' => [],
            ],
        ];

        return array_replace_recursive($payload, $overrides);
    }
}
