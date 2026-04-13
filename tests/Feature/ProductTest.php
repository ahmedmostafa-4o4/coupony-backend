<?php

namespace Tests\Feature;

use App\Domain\Product\Enums\ProductApprovalStatus;
use App\Domain\Product\Enums\ProductStatus;
use App\Domain\Product\Models\Category;
use App\Domain\Product\Models\Product;
use App\Domain\Product\Models\ProductVariant;
use App\Domain\Store\Models\Store;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'admin', 'guard_name' => 'sanctum']);
        Role::create(['name' => 'seller', 'guard_name' => 'sanctum']);
        Role::create(['name' => 'store_employee', 'guard_name' => 'sanctum']);

        Storage::fake('public');
        config(['logging.default' => 'null']);
    }

    public function test_seller_can_create_product_for_own_store(): void
    {
        $seller = $this->seller();
        $store = $this->storeFor($seller);
        $categories = Category::factory()->count(2)->create();

        $response = $this->actingAs($seller, 'sanctum')
            ->post("/api/v1/stores/{$store->id}/products", $this->payload([
                'category_ids' => $categories->pluck('id')->all(),
            ]));

        $response->assertCreated()
            ->assertJsonPath('data.store_id', $store->id)
            ->assertJsonPath('data.title', 'Example Product')
            ->assertJsonPath('data.approval_status', ProductApprovalStatus::PENDING->value)
            ->assertJsonPath('data.offer.type', 'fixed')
            ->assertJsonPath('data.offer.fixed_amount', '15.00')
            ->assertJsonPath('data.variants.0.title', 'Red / XL')
            ->assertJsonPath('data.variants.0.inventory_mode', 'tracked')
            ->assertJsonPath('data.variants.0.stock_qty', 8)
            ->assertJsonPath('data.variants.0.low_stock_threshold', 2)
            ->assertJsonPath('data.variants.0.allow_backorder', false)
            ->assertJsonPath('data.variants.0.is_in_stock', true);

        $this->assertDatabaseHas('products', [
            'store_id' => $store->id,
            'slug' => 'example-product',
            'status' => ProductStatus::INACTIVE->value,
            'approval_status' => ProductApprovalStatus::PENDING->value,
        ]);

        $this->assertDatabaseCount('product_images', 1);
        $this->assertDatabaseCount('product_variants', 1);
        $this->assertDatabaseCount('product_variant_attributes', 2);
        $this->assertDatabaseCount('product_categories', 2);
    }

    public function test_seller_cannot_create_product_for_another_sellers_store(): void
    {
        $seller = $this->seller();
        $otherSeller = $this->seller();
        $store = $this->storeFor($otherSeller);
        $category = Category::factory()->create();

        $response = $this->actingAs($seller, 'sanctum')
            ->post("/api/v1/stores/{$store->id}/products", $this->payload([
                'category_ids' => [$category->id],
            ]));

        $response->assertForbidden();
    }

    public function test_seller_can_list_own_store_products(): void
    {
        $seller = $this->seller();
        $store = $this->storeFor($seller);
        $otherStore = $this->storeFor($this->seller());

        Product::factory()->count(2)->create(['store_id' => $store->id]);
        Product::factory()->create(['store_id' => $otherStore->id]);

        $response = $this->actingAs($seller, 'sanctum')
            ->getJson("/api/v1/stores/{$store->id}/products");

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 2);
    }

    public function test_seller_can_view_own_product(): void
    {
        $seller = $this->seller();
        $store = $this->storeFor($seller);
        $product = Product::factory()->create(['store_id' => $store->id]);

        $response = $this->actingAs($seller, 'sanctum')
            ->getJson("/api/v1/stores/{$store->id}/products/{$product->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $product->id);
    }

    public function test_seller_can_update_own_product(): void
    {
        $seller = $this->seller();
        $store = $this->storeFor($seller);
        $product = Product::factory()->create([
            'store_id' => $store->id,
            'slug' => 'old-product',
        ]);
        $categories = Category::factory()->count(2)->create();

        $response = $this->actingAs($seller, 'sanctum')
            ->putJson("/api/v1/stores/{$store->id}/products/{$product->id}", [
                'title' => 'Updated Product',
                'slug' => 'updated-product',
                'category_ids' => [$categories[0]->id],
            ]);

        $response->assertOk()
            ->assertJsonPath('data.title', 'Updated Product')
            ->assertJsonPath('data.status', ProductStatus::INACTIVE->value);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'title' => 'Updated Product',
            'slug' => 'updated-product',
            'status' => ProductStatus::INACTIVE->value,
        ]);
        $this->assertDatabaseHas('product_categories', [
            'product_id' => $product->id,
            'category_id' => $categories[0]->id,
        ]);
    }

    public function test_seller_cannot_update_another_stores_product(): void
    {
        $seller = $this->seller();
        $otherSeller = $this->seller();
        $store = $this->storeFor($otherSeller);
        $product = Product::factory()->create(['store_id' => $store->id]);

        $response = $this->actingAs($seller, 'sanctum')
            ->putJson("/api/v1/stores/{$store->id}/products/{$product->id}", [
                'title' => 'Blocked Update',
            ]);

        $response->assertForbidden();
    }

    public function test_seller_can_delete_own_product(): void
    {
        $seller = $this->seller();
        $store = $this->storeFor($seller);
        $product = Product::factory()->create(['store_id' => $store->id]);
        ProductVariant::factory()->create(['product_id' => $product->id]);

        $response = $this->actingAs($seller, 'sanctum')
            ->deleteJson("/api/v1/stores/{$store->id}/products/{$product->id}");

        $response->assertOk()
            ->assertJsonPath('message', __('api.product.deleted'));

        $this->assertSoftDeleted('products', ['id' => $product->id]);
        $this->assertSoftDeleted('product_variants', ['product_id' => $product->id]);
    }

    public function test_public_list_only_returns_active_products(): void
    {
        Product::factory()->active()->approved()->create(['title' => 'Visible Product']);
        Product::factory()->active()->create(['title' => 'Pending Product']);

        $response = $this->getJson('/api/v1/products');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Visible Product');
    }

    public function test_public_show_rejects_non_active_products(): void
    {
        $product = Product::factory()->create(['status' => ProductStatus::INACTIVE]);

        $response = $this->getJson("/api/v1/products/{$product->id}");

        $response->assertNotFound()
            ->assertJsonPath('message', __('api.product.not_found'));
    }

    public function test_public_show_rejects_non_approved_products(): void
    {
        $product = Product::factory()->active()->create([
            'approval_status' => ProductApprovalStatus::PENDING,
        ]);

        $response = $this->getJson("/api/v1/products/{$product->id}");

        $response->assertNotFound()
            ->assertJsonPath('message', __('api.product.not_found'));
    }

    public function test_product_resource_matches_final_contract(): void
    {
        $seller = $this->seller();
        $store = $this->storeFor($seller);
        $product = Product::factory()->active()->approved()->create(['store_id' => $store->id]);
        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'is_active' => true,
            'inventory_mode' => 'tracked',
            'stock_qty' => 7,
            'low_stock_threshold' => 2,
            'allow_backorder' => false,
        ]);

        $response = $this->actingAs($seller, 'sanctum')
            ->getJson("/api/v1/stores/{$store->id}/products/{$product->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $product->id)
            ->assertJsonPath('data.store_id', $store->id)
            ->assertJsonPath('data.title', $product->title)
            ->assertJsonPath('data.currency', 'EGP')
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.approval_status', 'approved')
            ->assertJsonPath('data.is_featured', false)
            ->assertJsonPath('data.variants.0.inventory_mode', 'tracked')
            ->assertJsonPath('data.variants.0.stock_qty', 7)
            ->assertJsonPath('data.variants.0.low_stock_threshold', 2)
            ->assertJsonPath('data.variants.0.allow_backorder', false)
            ->assertJsonMissingPath('data.base_price')
            ->assertJsonMissingPath('data.compare_at_price')
            ->assertJsonMissingPath('data.sku')
            ->assertJsonMissingPath('data.published_revision_no')
            ->assertJsonMissingPath('data.approved_at')
            ->assertJsonMissingPath('data.updated_at');
    }

    public function test_category_sync_works(): void
    {
        $seller = $this->seller();
        $store = $this->storeFor($seller);
        $categories = Category::factory()->count(2)->create();

        $response = $this->actingAs($seller, 'sanctum')
            ->post("/api/v1/stores/{$store->id}/products", $this->payload([
                'category_ids' => $categories->pluck('id')->all(),
            ]));

        $productId = $response->json('data.id');

        $this->assertDatabaseCount('product_categories', 2);
        $this->assertDatabaseHas('product_categories', [
            'product_id' => $productId,
            'category_id' => $categories[0]->id,
        ]);
        $this->assertDatabaseHas('product_categories', [
            'product_id' => $productId,
            'category_id' => $categories[1]->id,
        ]);
    }

    public function test_variant_creation_works(): void
    {
        $seller = $this->seller();
        $store = $this->storeFor($seller);
        $category = Category::factory()->create();

        $response = $this->actingAs($seller, 'sanctum')
            ->post("/api/v1/stores/{$store->id}/products", $this->payload([
                'category_ids' => [$category->id],
            ]));

        $productId = $response->json('data.id');

        $this->assertDatabaseHas('product_variants', [
            'product_id' => $productId,
            'title' => 'Red / XL',
            'sku' => 'SKU-001-RED-XL',
            'is_default' => true,
            'inventory_mode' => 'tracked',
            'stock_qty' => 8,
            'low_stock_threshold' => 2,
            'allow_backorder' => false,
        ]);

        $variantId = ProductVariant::where('product_id', $productId)->value('id');

        $this->assertDatabaseHas('product_variant_attributes', [
            'variant_id' => $variantId,
            'attribute_name' => 'color',
            'attribute_value' => 'red',
        ]);
    }

    public function test_only_one_default_variant_is_enforced(): void
    {
        $seller = $this->seller();
        $store = $this->storeFor($seller);
        $category = Category::factory()->create();
        $payload = $this->payload([
            'category_ids' => [$category->id],
            'images' => [],
            'variants' => [
                [
                    'title' => 'Red / XL',
                    'option_summary' => 'Color: Red, Size: XL',
                    'sku' => 'SKU-001-RED-XL',
                    'barcode' => '123456789',
                    'price' => 110,
                    'compare_at_price' => 130,
                    'currency' => 'EGP',
                    'sort_order' => 0,
                    'is_default' => true,
                    'is_active' => true,
                    'attributes' => [],
                ],
                [
                    'title' => 'Blue / L',
                    'option_summary' => 'Color: Blue, Size: L',
                    'sku' => 'SKU-001-BLUE-L',
                    'barcode' => '987654321',
                    'price' => 115,
                    'compare_at_price' => 135,
                    'currency' => 'EGP',
                    'sort_order' => 1,
                    'is_default' => true,
                    'is_active' => true,
                    'attributes' => [],
                ],
            ],
        ]);

        $response = $this->actingAs($seller, 'sanctum')
            ->postJson("/api/v1/stores/{$store->id}/products", $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['variants']);
    }

    public function test_product_offer_is_required_on_create(): void
    {
        $seller = $this->seller();
        $store = $this->storeFor($seller);

        $response = $this->actingAs($seller, 'sanctum')
            ->postJson("/api/v1/stores/{$store->id}/products", $this->payload([
                'offer' => null,
                'images' => [],
            ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['offer']);
    }

    public function test_fixed_offer_requires_fixed_amount(): void
    {
        $seller = $this->seller();
        $store = $this->storeFor($seller);

        $response = $this->actingAs($seller, 'sanctum')
            ->postJson("/api/v1/stores/{$store->id}/products", $this->payload([
                'images' => [],
                'offer' => [
                    'type' => 'fixed',
                    'status' => 'active',
                    'fixed_amount' => null,
                ],
            ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['offer.fixed_amount']);
    }

    public function test_percentage_offer_requires_percentage_value(): void
    {
        $seller = $this->seller();
        $store = $this->storeFor($seller);

        $response = $this->actingAs($seller, 'sanctum')
            ->postJson("/api/v1/stores/{$store->id}/products", $this->payload([
                'images' => [],
                'offer' => [
                    'type' => 'percentage',
                    'status' => 'active',
                    'fixed_amount' => null,
                    'percentage_value' => null,
                    'max_discount' => null,
                ],
            ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['offer.percentage_value']);
    }

    public function test_buy_x_get_y_offer_requires_known_target_variant_skus(): void
    {
        $seller = $this->seller();
        $store = $this->storeFor($seller);

        $response = $this->actingAs($seller, 'sanctum')
            ->postJson("/api/v1/stores/{$store->id}/products", $this->payload([
                'images' => [],
                'offer' => [
                    'type' => 'buy_x_get_y',
                    'status' => 'active',
                    'buy_qty' => 2,
                    'get_qty' => 1,
                    'allow_mix_buy_variants' => true,
                    'allow_mix_reward_variants' => false,
                    'buy_variant_skus' => ['MISSING-SKU'],
                    'reward_variant_skus' => ['SKU-001-RED-XL'],
                ],
            ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['offer.buy_variant_skus']);
    }

    public function test_buy_x_get_y_offer_returns_target_variant_ids(): void
    {
        $seller = $this->seller();
        $store = $this->storeFor($seller);

        $response = $this->actingAs($seller, 'sanctum')
            ->postJson("/api/v1/stores/{$store->id}/products", $this->payload([
                'images' => [],
                'variants' => [
                    [
                        'title' => 'Red / XL',
                        'option_summary' => 'Color: Red, Size: XL',
                        'sku' => 'SKU-001-RED-XL',
                        'barcode' => '123456789',
                        'price' => 110,
                        'compare_at_price' => 130,
                        'currency' => 'EGP',
                        'sort_order' => 0,
                        'is_default' => true,
                        'is_active' => true,
                        'inventory_mode' => 'tracked',
                        'stock_qty' => 8,
                        'low_stock_threshold' => 2,
                        'allow_backorder' => false,
                        'attributes' => [],
                    ],
                    [
                        'title' => 'Blue / L',
                        'option_summary' => 'Color: Blue, Size: L',
                        'sku' => 'SKU-001-BLUE-L',
                        'barcode' => '987654321',
                        'price' => 115,
                        'compare_at_price' => 135,
                        'currency' => 'EGP',
                        'sort_order' => 1,
                        'is_default' => false,
                        'is_active' => true,
                        'inventory_mode' => 'tracked',
                        'stock_qty' => 5,
                        'low_stock_threshold' => 1,
                        'allow_backorder' => false,
                        'attributes' => [],
                    ],
                ],
                'offer' => [
                    'type' => 'buy_x_get_y',
                    'status' => 'active',
                    'label' => 'Buy 2 get 1',
                    'buy_qty' => 2,
                    'get_qty' => 1,
                    'allow_mix_buy_variants' => true,
                    'allow_mix_reward_variants' => true,
                    'buy_variant_skus' => ['SKU-001-RED-XL', 'SKU-001-BLUE-L'],
                    'reward_variant_skus' => ['SKU-001-BLUE-L'],
                ],
            ]));

        $response->assertCreated()
            ->assertJsonPath('data.offer.type', 'buy_x_get_y')
            ->assertJsonCount(2, 'data.offer.buy_variant_ids')
            ->assertJsonCount(1, 'data.offer.reward_variant_ids');
    }

    public function test_tracked_inventory_requires_stock_quantity(): void
    {
        $seller = $this->seller();
        $store = $this->storeFor($seller);
        $category = Category::factory()->create();

        $response = $this->actingAs($seller, 'sanctum')
            ->postJson("/api/v1/stores/{$store->id}/products", $this->payload([
                'category_ids' => [$category->id],
                'images' => [],
                'variants' => [[
                    'title' => 'Tracked Variant',
                    'option_summary' => 'Tracked stock',
                    'sku' => 'TRACKED-NO-STOCK',
                    'barcode' => '111222333',
                    'price' => 110,
                    'compare_at_price' => 130,
                    'currency' => 'EGP',
                    'sort_order' => 0,
                    'is_default' => true,
                    'is_active' => true,
                    'inventory_mode' => 'tracked',
                    'stock_qty' => null,
                    'low_stock_threshold' => null,
                    'allow_backorder' => false,
                    'attributes' => [],
                ]],
            ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['variants.0.stock_qty']);
    }

    public function test_unlimited_inventory_is_always_in_stock(): void
    {
        $seller = $this->seller();
        $store = $this->storeFor($seller);
        $category = Category::factory()->create();

        $response = $this->actingAs($seller, 'sanctum')
            ->postJson("/api/v1/stores/{$store->id}/products", $this->payload([
                'category_ids' => [$category->id],
                'images' => [],
                'variants' => [[
                    'title' => 'Unlimited Variant',
                    'option_summary' => 'Unlimited stock',
                    'sku' => 'UNLIMITED-STOCK',
                    'barcode' => '999888777',
                    'price' => 110,
                    'compare_at_price' => 130,
                    'currency' => 'EGP',
                    'sort_order' => 0,
                    'is_default' => true,
                    'is_active' => true,
                    'inventory_mode' => 'unlimited',
                    'stock_qty' => null,
                    'low_stock_threshold' => null,
                    'allow_backorder' => false,
                    'attributes' => [],
                ]],
            ]));

        $response->assertCreated()
            ->assertJsonPath('data.variants.0.inventory_mode', 'unlimited')
            ->assertJsonPath('data.variants.0.stock_qty', null)
            ->assertJsonPath('data.variants.0.low_stock_threshold', null)
            ->assertJsonPath('data.variants.0.is_in_stock', true);
    }

    public function test_tracked_inventory_with_backorder_is_reported_in_stock(): void
    {
        $seller = $this->seller();
        $store = $this->storeFor($seller);
        $product = Product::factory()->create(['store_id' => $store->id]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'inventory_mode' => 'tracked',
            'stock_qty' => 0,
            'low_stock_threshold' => 3,
            'allow_backorder' => true,
        ]);

        $response = $this->actingAs($seller, 'sanctum')
            ->getJson("/api/v1/stores/{$store->id}/products/{$product->id}");

        $response->assertOk()
            ->assertJsonPath('data.variants.0.inventory_mode', 'tracked')
            ->assertJsonPath('data.variants.0.stock_qty', 0)
            ->assertJsonPath('data.variants.0.allow_backorder', true)
            ->assertJsonPath('data.variants.0.is_in_stock', true);
    }

    public function test_slug_uniqueness_per_store_is_enforced(): void
    {
        $seller = $this->seller();
        $store = $this->storeFor($seller);
        $category = Category::factory()->create();

        Product::factory()->create([
            'store_id' => $store->id,
            'slug' => 'example-product',
        ]);

        $response = $this->actingAs($seller, 'sanctum')
            ->postJson("/api/v1/stores/{$store->id}/products", $this->payload([
                'category_ids' => [$category->id],
                'images' => [],
            ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['slug']);
    }

    public function test_variant_sku_uniqueness_per_product_is_enforced(): void
    {
        $seller = $this->seller();
        $store = $this->storeFor($seller);
        $category = Category::factory()->create();
        $payload = $this->payload([
            'category_ids' => [$category->id],
            'images' => [],
            'variants' => [
                [
                    'title' => 'Red / XL',
                    'option_summary' => 'Color: Red, Size: XL',
                    'sku' => 'DUPLICATE-SKU',
                    'barcode' => '123456789',
                    'price' => 110,
                    'compare_at_price' => 130,
                    'currency' => 'EGP',
                    'sort_order' => 0,
                    'is_default' => true,
                    'is_active' => true,
                    'attributes' => [],
                ],
                [
                    'title' => 'Blue / L',
                    'option_summary' => 'Color: Blue, Size: L',
                    'sku' => 'DUPLICATE-SKU',
                    'barcode' => '987654321',
                    'price' => 115,
                    'compare_at_price' => 135,
                    'currency' => 'EGP',
                    'sort_order' => 1,
                    'is_default' => false,
                    'is_active' => true,
                    'attributes' => [],
                ],
            ],
        ]);

        $response = $this->actingAs($seller, 'sanctum')
            ->postJson("/api/v1/stores/{$store->id}/products", $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['variants']);
    }

    public function test_nested_store_product_route_requires_matching_store(): void
    {
        $seller = $this->seller();
        $store = $this->storeFor($seller);
        $anotherStore = $this->storeFor($seller);
        $product = Product::factory()->create(['store_id' => $anotherStore->id]);

        $response = $this->actingAs($seller, 'sanctum')
            ->getJson("/api/v1/stores/{$store->id}/products/{$product->id}");

        $response->assertNotFound();
    }

    public function test_seller_can_manage_variants_through_separate_endpoints(): void
    {
        $seller = $this->seller();
        $store = $this->storeFor($seller);
        $product = Product::factory()->create(['store_id' => $store->id]);

        $createResponse = $this->actingAs($seller, 'sanctum')
            ->postJson("/api/v1/stores/{$store->id}/products/{$product->id}/variants", [
                'title' => 'Blue / L',
                'option_summary' => 'Color: Blue, Size: L',
                'sku' => 'BLUE-L',
                'barcode' => '123456001',
                'price' => 140,
                    'compare_at_price' => 160,
                    'currency' => 'EGP',
                    'is_default' => true,
                    'is_active' => true,
                    'inventory_mode' => 'tracked',
                    'stock_qty' => 0,
                    'low_stock_threshold' => 5,
                    'allow_backorder' => true,
                    'attributes' => [
                        ['attribute_name' => 'color', 'attribute_value' => 'blue', 'sort_order' => 0],
                        ['attribute_name' => 'size', 'attribute_value' => 'L', 'sort_order' => 1],
                ],
            ]);

        $variantId = $createResponse->json('data.id');

        $createResponse->assertCreated()
            ->assertJsonPath('data.sku', 'BLUE-L')
            ->assertJsonPath('data.is_default', true)
            ->assertJsonPath('data.inventory_mode', 'tracked')
            ->assertJsonPath('data.stock_qty', 0)
            ->assertJsonPath('data.low_stock_threshold', 5)
            ->assertJsonPath('data.allow_backorder', true)
            ->assertJsonPath('data.is_in_stock', true);

        $updateResponse = $this->actingAs($seller, 'sanctum')
            ->putJson("/api/v1/stores/{$store->id}/products/{$product->id}/variants/{$variantId}", [
                'title' => 'Blue / XL',
                'price' => 150,
                'compare_at_price' => 170,
                'is_active' => false,
                'stock_qty' => 4,
            ]);

        $updateResponse->assertOk()
            ->assertJsonPath('data.title', 'Blue / XL')
            ->assertJsonPath('data.is_active', false)
            ->assertJsonPath('data.stock_qty', 4);

        $this->assertDatabaseHas('product_variants', [
            'id' => $variantId,
            'title' => 'Blue / XL',
            'is_active' => false,
            'inventory_mode' => 'tracked',
            'stock_qty' => 4,
            'allow_backorder' => true,
        ]);
    }

    public function test_separate_variant_default_endpoint_behavior_unsets_previous_default(): void
    {
        $seller = $this->seller();
        $store = $this->storeFor($seller);
        $product = Product::factory()->create(['store_id' => $store->id]);
        $existingDefault = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'is_default' => true,
            'sku' => 'DEFAULT-ONE',
        ]);

        $response = $this->actingAs($seller, 'sanctum')
            ->postJson("/api/v1/stores/{$store->id}/products/{$product->id}/variants", [
                'title' => 'Second Variant',
                'sku' => 'DEFAULT-TWO',
                'price' => 125,
                'compare_at_price' => 145,
                'currency' => 'EGP',
                'is_default' => true,
            ]);

        $newVariantId = $response->json('data.id');

        $response->assertCreated()
            ->assertJsonPath('data.is_default', true);

        $this->assertDatabaseHas('product_variants', [
            'id' => $existingDefault->id,
            'is_default' => false,
        ]);
        $this->assertDatabaseHas('product_variants', [
            'id' => $newVariantId,
            'is_default' => true,
        ]);
    }

    public function test_seller_can_replace_variant_attributes_through_separate_endpoint(): void
    {
        $seller = $this->seller();
        $store = $this->storeFor($seller);
        $product = Product::factory()->create(['store_id' => $store->id]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'sku' => 'ATTR-ONE',
        ]);
        $variant->attributes()->create([
            'attribute_name' => 'color',
            'attribute_value' => 'red',
            'sort_order' => 0,
            'created_at' => now(),
        ]);

        $response = $this->actingAs($seller, 'sanctum')
            ->putJson("/api/v1/stores/{$store->id}/products/{$product->id}/variants/{$variant->id}/attributes", [
                'attributes' => [
                    ['attribute_name' => 'material', 'attribute_value' => 'cotton', 'sort_order' => 0],
                    ['attribute_name' => 'size', 'attribute_value' => 'XL', 'sort_order' => 1],
                ],
            ]);

        $response->assertOk()
            ->assertJsonCount(2, 'data.attributes');

        $this->assertDatabaseMissing('product_variant_attributes', [
            'variant_id' => $variant->id,
            'attribute_name' => 'color',
        ]);
        $this->assertDatabaseHas('product_variant_attributes', [
            'variant_id' => $variant->id,
            'attribute_name' => 'material',
        ]);
    }

    public function test_seller_can_manage_images_through_separate_endpoints(): void
    {
        $seller = $this->seller();
        $store = $this->storeFor($seller);
        $product = Product::factory()->create(['store_id' => $store->id]);

        $createResponse = $this->actingAs($seller, 'sanctum')
            ->post("/api/v1/stores/{$store->id}/products/{$product->id}/images", [
                'images' => [
                    [
                        'file' => UploadedFile::fake()->create('primary.jpg', 100, 'image/jpeg'),
                        'sort_order' => 0,
                        'is_primary' => true,
                    ],
                    [
                        'file' => UploadedFile::fake()->create('secondary.jpg', 100, 'image/jpeg'),
                        'sort_order' => 1,
                        'is_primary' => false,
                    ],
                ],
            ]);

        $firstImageId = $createResponse->json('data.0.id');
        $secondImageId = $createResponse->json('data.1.id');

        $createResponse->assertCreated()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.is_primary', true);

        $setPrimaryResponse = $this->actingAs($seller, 'sanctum')
            ->patchJson("/api/v1/stores/{$store->id}/products/{$product->id}/images/{$secondImageId}/primary");

        $setPrimaryResponse->assertOk()
            ->assertJsonPath('data.id', $secondImageId)
            ->assertJsonPath('data.is_primary', true);

        $reorderResponse = $this->actingAs($seller, 'sanctum')
            ->patchJson("/api/v1/stores/{$store->id}/products/{$product->id}/images/reorder", [
                'images' => [
                    ['id' => $firstImageId, 'sort_order' => 2],
                    ['id' => $secondImageId, 'sort_order' => 0],
                ],
            ]);

        $reorderResponse->assertOk()
            ->assertJsonPath('data.0.id', $secondImageId);

        $this->assertDatabaseHas('product_images', [
            'id' => $firstImageId,
            'is_primary' => false,
            'sort_order' => 2,
        ]);
        $this->assertDatabaseHas('product_images', [
            'id' => $secondImageId,
            'is_primary' => true,
            'sort_order' => 0,
        ]);
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
            'title' => 'Example Product',
            'slug' => 'example-product',
            'short_description' => 'Short description',
            'description' => 'Long description',
            'base_price' => 100,
            'compare_at_price' => 120,
            'currency' => 'EGP',
            'sku' => 'SKU-001',
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
                    'sku' => 'SKU-001-RED-XL',
                    'barcode' => '123456789',
                    'price' => 110,
                    'compare_at_price' => 130,
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
                        [
                            'attribute_name' => 'size',
                            'attribute_value' => 'XL',
                            'sort_order' => 1,
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
