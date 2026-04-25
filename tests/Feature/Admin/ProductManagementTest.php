<?php

namespace Tests\Feature\Admin;

use App\Domain\Product\Enums\ProductApprovalStatus;
use App\Domain\Product\Enums\ProductRevisionStatus;
use App\Domain\Product\Enums\ProductStatus;
use App\Domain\Product\Models\Category;
use App\Domain\Product\Models\Product;
use App\Domain\Product\Models\ProductRevision;
use App\Domain\Store\Models\Store;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'admin', 'guard_name' => 'sanctum']);
        Role::create(['name' => 'seller', 'guard_name' => 'sanctum']);
        Role::create(['name' => 'customer', 'guard_name' => 'sanctum']);

        Storage::fake('public');
        config(['logging.default' => 'null']);
    }

    public function test_admin_can_list_products(): void
    {
        Product::factory()->count(2)->create();

        $response = $this->actingAs($this->admin(), 'sanctum')
            ->getJson('/api/v1/admin/products');

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 2);
    }

    public function test_admin_can_filter_and_search_products(): void
    {
        $store = $this->storeFor($this->seller());
        Product::factory()->active()->approved()->create([
            'store_id' => $store->id,
            'title' => 'Matching Laptop',
        ]);
        Product::factory()->create([
            'title' => 'Other Product',
            'status' => ProductStatus::INACTIVE,
            'approval_status' => ProductApprovalStatus::PENDING,
        ]);

        $response = $this->actingAs($this->admin(), 'sanctum')
            ->getJson("/api/v1/admin/products?search=Laptop&status=active&approval_status=approved&store_id={$store->id}");

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Matching Laptop');
    }

    public function test_admin_can_view_a_single_product(): void
    {
        $product = Product::factory()->create();

        $this->actingAs($this->admin(), 'sanctum')
            ->getJson("/api/v1/admin/products/{$product->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $product->id);
    }

    public function test_admin_can_create_a_product(): void
    {
        $admin = $this->admin();
        $store = $this->storeFor($this->seller());
        $category = Category::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->post("/api/v1/admin/products", $this->payload([
                'store_id' => $store->id,
                'category_ids' => [$category->id],
            ]));

        $productId = $response->json('data.id');

        $response->assertCreated()
            ->assertJsonPath('data.store_id', $store->id)
            ->assertJsonPath('data.status', ProductStatus::ACTIVE->value)
            ->assertJsonPath('data.approval_status', ProductApprovalStatus::APPROVED->value);

        $this->assertDatabaseHas('products', [
            'id' => $productId,
            'store_id' => $store->id,
            'sku' => 'ADMIN-SKU-001',
            'status' => ProductStatus::ACTIVE->value,
            'approval_status' => ProductApprovalStatus::APPROVED->value,
            'approved_by' => $admin->id,
        ]);
        $this->assertDatabaseHas('product_categories', [
            'product_id' => $productId,
            'category_id' => $category->id,
        ]);
    }

    public function test_admin_create_without_slug_and_sku_generates_identifiers(): void
    {
        $admin = $this->admin();
        $store = $this->storeFor($this->seller());

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/products', $this->payload([
                'store_id' => $store->id,
                'title' => 'Running Shoes',
                'slug' => null,
                'sku' => null,
                'images' => [],
                'variants' => [
                    [
                        'title' => 'Blue / XL',
                        'option_summary' => 'Color: Blue, Size: XL',
                        'sku' => null,
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
                            ['attribute_name' => 'color', 'attribute_value' => 'blue', 'sort_order' => 0],
                            ['attribute_name' => 'size', 'attribute_value' => 'XL', 'sort_order' => 1],
                        ],
                    ],
                ],
            ]));

        $productId = $response->json('data.id');

        $response->assertCreated()
            ->assertJsonPath('data.slug', 'running-shoes')
            ->assertJsonPath('data.variants.0.sku', 'VAR-SHO-RUN-BLU-XL');

        $this->assertDatabaseHas('products', [
            'id' => $productId,
            'slug' => 'running-shoes',
            'sku' => 'PRD-SHO-RUN',
        ]);
    }

    public function test_admin_create_buy_x_get_y_accepts_generated_variant_sku_targets(): void
    {
        $admin = $this->admin();
        $store = $this->storeFor($this->seller());

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/products', $this->payload([
                'store_id' => $store->id,
                'title' => 'Running Shoes',
                'slug' => null,
                'sku' => null,
                'images' => [],
                'variants' => [
                    [
                        'title' => 'Black / 42',
                        'option_summary' => 'Color: Black, Size: 42',
                        'sku' => null,
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
                            ['attribute_name' => 'color', 'attribute_value' => 'black', 'sort_order' => 0],
                            ['attribute_name' => 'size', 'attribute_value' => '42', 'sort_order' => 1],
                        ],
                    ],
                ],
                'offer' => [
                    'type' => 'buy_x_get_y',
                    'status' => 'active',
                    'buy_qty' => 1,
                    'get_qty' => 1,
                    'allow_mix_buy_variants' => false,
                    'allow_mix_reward_variants' => false,
                    'buy_variant_skus' => ['VAR-SHO-RUN-BLK-42'],
                    'reward_variant_skus' => ['VAR-SHO-RUN-BLK-42'],
                ],
            ]));

        $response->assertCreated()
            ->assertJsonPath('data.offer.type', 'buy_x_get_y')
            ->assertJsonPath('data.variants.0.sku', 'VAR-SHO-RUN-BLK-42');
    }

    public function test_admin_can_update_an_approved_product_directly(): void
    {
        $product = Product::factory()->active()->approved()->create([
            'title' => 'Before Admin Edit',
            'slug' => 'before-admin-edit',
        ]);

        $this->actingAs($this->admin(), 'sanctum')
            ->patchJson("/api/v1/admin/products/{$product->id}", [
                'title' => 'After Admin Edit',
                'slug' => 'after-admin-edit',
            ])
            ->assertOk()
            ->assertJsonPath('data.title', 'After Admin Edit')
            ->assertJsonPath('data.status', ProductStatus::ACTIVE->value)
            ->assertJsonPath('data.approval_status', ProductApprovalStatus::APPROVED->value);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'title' => 'After Admin Edit',
            'slug' => 'after-admin-edit',
            'status' => ProductStatus::ACTIVE->value,
            'approval_status' => ProductApprovalStatus::APPROVED->value,
        ]);
    }

    public function test_admin_update_does_not_create_a_pending_revision(): void
    {
        $product = Product::factory()->active()->approved()->create([
            'title' => 'Live Product',
            'slug' => 'live-product',
            'published_revision_no' => 1,
        ]);

        $this->actingAs($this->admin(), 'sanctum')
            ->patchJson("/api/v1/admin/products/{$product->id}", [
                'title' => 'Live Product Updated',
            ])
            ->assertOk()
            ->assertJsonPath('data.title', 'Live Product Updated');

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'title' => 'Live Product Updated',
        ]);
        $this->assertDatabaseMissing('product_revisions', [
            'product_id' => $product->id,
            'status' => ProductRevisionStatus::PENDING->value,
        ]);
    }

    public function test_admin_can_delete_a_product(): void
    {
        $product = Product::factory()->create();

        $this->actingAs($this->admin(), 'sanctum')
            ->deleteJson("/api/v1/admin/products/{$product->id}")
            ->assertOk();

        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }

    public function test_non_admin_cannot_access_admin_product_crud_endpoints(): void
    {
        $seller = $this->seller();
        $store = $this->storeFor($seller);
        $product = Product::factory()->create(['store_id' => $store->id]);

        $this->actingAs($seller, 'sanctum')
            ->getJson('/api/v1/admin/products')
            ->assertForbidden();

        $this->actingAs($seller, 'sanctum')
            ->getJson("/api/v1/admin/products/{$product->id}")
            ->assertForbidden();

        $this->actingAs($seller, 'sanctum')
            ->postJson('/api/v1/admin/products', $this->payload(['store_id' => $store->id, 'images' => []]))
            ->assertForbidden();

        $this->actingAs($seller, 'sanctum')
            ->patchJson("/api/v1/admin/products/{$product->id}", ['title' => 'Nope'])
            ->assertForbidden();

        $this->actingAs($seller, 'sanctum')
            ->deleteJson("/api/v1/admin/products/{$product->id}")
            ->assertForbidden();
    }

    public function test_seller_direct_field_update_applies_live_after_approval_without_pending_revision(): void
    {
        $seller = $this->seller();
        $admin = $this->admin();
        $store = $this->storeFor($seller);

        $createResponse = $this->actingAs($seller, 'sanctum')
            ->post("/api/v1/stores/{$store->id}/products", $this->payload());

        $productId = $createResponse->json('data.id');
        $revisionId = ProductRevision::query()->where('product_id', $productId)->value('id');

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/admin/products/revisions/{$revisionId}/approve")
            ->assertOk();

        $this->actingAs($seller, 'sanctum')
            ->putJson("/api/v1/stores/{$store->id}/products/{$productId}", [
                'title' => 'Seller Pending Edit',
            ])
            ->assertOk()
            ->assertJsonPath('data.title', 'Seller Pending Edit')
            ->assertJsonPath('data.pending_revision', null);
    }

    public function test_existing_admin_revision_endpoints_still_work(): void
    {
        $seller = $this->seller();
        $admin = $this->admin();
        $store = $this->storeFor($seller);

        $createResponse = $this->actingAs($seller, 'sanctum')
            ->post("/api/v1/stores/{$store->id}/products", $this->payload());

        $productId = $createResponse->json('data.id');
        $revisionId = ProductRevision::query()->where('product_id', $productId)->value('id');

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/products/pending')
            ->assertOk()
            ->assertJsonPath('data.0.id', $revisionId);

        $this->actingAs($admin, 'sanctum')
            ->getJson("/api/v1/admin/products/revisions/{$revisionId}")
            ->assertOk()
            ->assertJsonPath('data.id', $revisionId);

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/admin/products/revisions/{$revisionId}/approve")
            ->assertOk()
            ->assertJsonPath('data.id', $productId);
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

    private function storeFor(User $user): Store
    {
        return Store::factory()->create([
            'owner_user_id' => $user->id,
        ]);
    }

    private function payload(array $overrides = []): array
    {
        $payload = [
            'title' => 'Admin Managed Product',
            'slug' => 'admin-managed-product',
            'short_description' => 'Short description',
            'description' => 'Long description',
            'currency' => 'EGP',
            'sku' => 'ADMIN-SKU-001',
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
                    'sku' => 'ADMIN-SKU-001-RED-XL',
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
