<?php

namespace Tests\Feature;

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
            ->assertJsonPath('data.variants.0.title', 'Red / XL');

        $this->assertDatabaseHas('products', [
            'store_id' => $store->id,
            'slug' => 'example-product',
            'status' => 'draft',
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
                'status' => ProductStatus::ACTIVE->value,
                'category_ids' => [$categories[0]->id],
            ]);

        $response->assertOk()
            ->assertJsonPath('data.title', 'Updated Product')
            ->assertJsonPath('data.status', ProductStatus::ACTIVE->value);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'title' => 'Updated Product',
            'slug' => 'updated-product',
            'status' => ProductStatus::ACTIVE->value,
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
        Product::factory()->active()->create(['title' => 'Visible Product']);
        Product::factory()->create(['title' => 'Draft Product', 'status' => ProductStatus::DRAFT]);

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
                'attributes' => [
                    ['attribute_name' => 'color', 'attribute_value' => 'blue', 'sort_order' => 0],
                    ['attribute_name' => 'size', 'attribute_value' => 'L', 'sort_order' => 1],
                ],
            ]);

        $variantId = $createResponse->json('data.id');

        $createResponse->assertCreated()
            ->assertJsonPath('data.sku', 'BLUE-L')
            ->assertJsonPath('data.is_default', true);

        $updateResponse = $this->actingAs($seller, 'sanctum')
            ->putJson("/api/v1/stores/{$store->id}/products/{$product->id}/variants/{$variantId}", [
                'title' => 'Blue / XL',
                'price' => 150,
                'compare_at_price' => 170,
                'is_active' => false,
            ]);

        $updateResponse->assertOk()
            ->assertJsonPath('data.title', 'Blue / XL')
            ->assertJsonPath('data.is_active', false);

        $this->assertDatabaseHas('product_variants', [
            'id' => $variantId,
            'title' => 'Blue / XL',
            'is_active' => false,
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
            'product_type' => 'standard',
            'base_price' => 100,
            'compare_at_price' => 120,
            'currency' => 'EGP',
            'sku' => 'SKU-001',
            'status' => 'draft',
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
        ];

        return array_replace_recursive($payload, $overrides);
    }
}
