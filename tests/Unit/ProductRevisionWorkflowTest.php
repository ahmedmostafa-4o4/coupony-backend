<?php

namespace Tests\Unit;

use App\Domain\Product\Actions\ApproveProductRevision;
use App\Domain\Product\Actions\CreateProduct;
use App\Domain\Product\Actions\RejectProductRevision;
use App\Domain\Product\Actions\UpdateProduct;
use App\Domain\Product\DTOs\ProductData;
use App\Domain\Product\Enums\ProductApprovalStatus;
use App\Domain\Product\Enums\ProductRevisionStatus;
use App\Domain\Product\Enums\ProductStatus;
use App\Domain\Store\Models\Store;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductRevisionWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'admin', 'guard_name' => 'sanctum']);
        Role::create(['name' => 'seller', 'guard_name' => 'sanctum']);
        Storage::fake('public');
    }

    public function test_create_generates_pending_revision_number_one(): void
    {
        $seller = $this->seller();
        $store = $this->storeFor($seller);

        /** @var CreateProduct $action */
        $action = app(CreateProduct::class);
        $product = $action->execute($store, $this->productData(), $seller);

        $product->refresh();
        $revision = $product->revisions()->first();

        $this->assertNotNull($revision);
        $this->assertSame(ProductStatus::INACTIVE->value, $product->status->value);
        $this->assertSame(ProductApprovalStatus::PENDING->value, $product->approval_status->value);
        $this->assertSame(1, $revision->revision_no);
        $this->assertSame(ProductRevisionStatus::PENDING->value, $revision->status->value);
        $this->assertSame('Example Product', data_get($revision->payload, 'product.title'));
        $this->assertSame('fixed', data_get($revision->payload, 'offer.type'));
        $this->assertCount(1, data_get($revision->payload, 'images'));
        $this->assertCount(1, data_get($revision->payload, 'variants'));
    }

    public function test_approve_applies_revision_and_marks_live_product_approved(): void
    {
        $seller = $this->seller();
        $admin = $this->admin();
        $store = $this->storeFor($seller);

        /** @var CreateProduct $create */
        $create = app(CreateProduct::class);
        $product = $create->execute($store, $this->productData(), $seller);
        $revision = $product->revisions()->firstOrFail();

        /** @var ApproveProductRevision $approve */
        $approve = app(ApproveProductRevision::class);
        $approvedProduct = $approve->execute($revision, $admin, 'Looks good');

        $approvedProduct->refresh();
        $revision->refresh();

        $this->assertSame(ProductStatus::ACTIVE->value, $approvedProduct->status->value);
        $this->assertSame(ProductApprovalStatus::APPROVED->value, $approvedProduct->approval_status->value);
        $this->assertSame(1, $approvedProduct->published_revision_no);
        $this->assertSame(ProductRevisionStatus::APPROVED->value, $revision->status->value);
        $this->assertSame('Example Product', $approvedProduct->title);
        $this->assertSame('Launch discount', $approvedProduct->offer->label);
        $this->assertCount(1, $approvedProduct->variants);
    }

    public function test_reject_keeps_live_data_unchanged(): void
    {
        $seller = $this->seller();
        $admin = $this->admin();
        $store = $this->storeFor($seller);

        /** @var CreateProduct $create */
        $create = app(CreateProduct::class);
        $product = $create->execute($store, $this->productData(), $seller);
        $revision = $product->revisions()->firstOrFail();

        /** @var RejectProductRevision $reject */
        $reject = app(RejectProductRevision::class);
        $reject->execute($revision, $admin, 'Needs more detail', 'Please revise');

        $product->refresh();
        $revision->refresh();

        $this->assertSame(ProductRevisionStatus::REJECTED->value, $revision->status->value);
        $this->assertSame(ProductApprovalStatus::REJECTED->value, $product->approval_status->value);
        $this->assertSame(ProductStatus::INACTIVE->value, $product->status->value);
        $this->assertSame('Example Product', $product->title);
    }

    public function test_approved_reviewable_update_creates_pending_revision_with_review_fields_and_full_snapshot(): void
    {
        $seller = $this->seller();
        $admin = $this->admin();
        $store = $this->storeFor($seller);

        /** @var CreateProduct $create */
        $create = app(CreateProduct::class);
        $product = $create->execute($store, $this->productData(), $seller);

        /** @var ApproveProductRevision $approve */
        $approve = app(ApproveProductRevision::class);
        $approve->execute($product->revisions()->firstOrFail(), $admin);

        /** @var UpdateProduct $update */
        $update = app(UpdateProduct::class);
        $update->execute($product->fresh(), $this->partialProductData([
            'short_description' => 'Edited pending short description',
        ]), $seller);

        $product->refresh();
        $pendingRevision = $product->revisions()->where('status', ProductRevisionStatus::PENDING)->first();

        $this->assertNotNull($pendingRevision);
        $this->assertSame('Example Product', $product->title);
        $this->assertSame(['short_description'], $pendingRevision->review_fields);
        $this->assertSame('Edited pending short description', data_get($pendingRevision->payload, 'product.short_description'));
        $this->assertIsArray(data_get($pendingRevision->payload, 'product'));
        $this->assertIsArray(data_get($pendingRevision->payload, 'images'));
        $this->assertIsArray(data_get($pendingRevision->payload, 'variants'));
        $this->assertArrayHasKey('offer', $pendingRevision->payload);
    }

    public function test_approved_direct_only_update_applies_immediately_without_creating_pending_revision(): void
    {
        $seller = $this->seller();
        $admin = $this->admin();
        $store = $this->storeFor($seller);

        /** @var CreateProduct $create */
        $create = app(CreateProduct::class);
        $product = $create->execute($store, $this->productData(), $seller);

        /** @var ApproveProductRevision $approve */
        $approve = app(ApproveProductRevision::class);
        $approve->execute($product->revisions()->firstOrFail(), $admin);

        /** @var UpdateProduct $update */
        $update = app(UpdateProduct::class);
        $update->execute($product->fresh(), $this->partialProductData([
            'is_featured' => true,
        ]), $seller);

        $product->refresh();

        $this->assertTrue($product->is_featured);
        $this->assertSame(0, $product->revisions()->where('status', ProductRevisionStatus::PENDING)->count());
    }

    public function test_approved_title_and_description_update_live_without_creating_pending_revision(): void
    {
        $seller = $this->seller();
        $admin = $this->admin();
        $store = $this->storeFor($seller);

        /** @var CreateProduct $create */
        $create = app(CreateProduct::class);
        $product = $create->execute($store, $this->productData(), $seller);

        /** @var ApproveProductRevision $approve */
        $approve = app(ApproveProductRevision::class);
        $approve->execute($product->revisions()->firstOrFail(), $admin);

        /** @var UpdateProduct $update */
        $update = app(UpdateProduct::class);
        $update->execute($product->fresh(), $this->partialProductData([
            'title' => 'Direct title',
            'description' => 'Direct description',
        ]), $seller);

        $product->refresh();

        $this->assertSame('Direct title', $product->title);
        $this->assertSame('Direct description', $product->description);
        $this->assertSame(0, $product->revisions()->where('status', ProductRevisionStatus::PENDING)->count());
    }

    public function test_approved_variant_direct_only_update_applies_immediately_without_creating_pending_revision(): void
    {
        $seller = $this->seller();
        $admin = $this->admin();
        $store = $this->storeFor($seller);

        /** @var CreateProduct $create */
        $create = app(CreateProduct::class);
        $product = $create->execute($store, $this->productData(), $seller);

        /** @var ApproveProductRevision $approve */
        $approve = app(ApproveProductRevision::class);
        $approve->execute($product->revisions()->firstOrFail(), $admin);

        /** @var UpdateProduct $update */
        $update = app(UpdateProduct::class);
        $update->execute($product->fresh(), $this->partialProductData([
            'variants' => [[
                'title' => 'Direct Variant Title',
                'option_summary' => 'Color: Red, Size: XL',
                'sku' => 'SKU-001-RED-XL',
                'barcode' => '987654321',
                'original_price' => 110,
                'currency' => 'EGP',
                'sort_order' => 3,
                'is_default' => true,
                'is_active' => false,
                'inventory_mode' => 'tracked',
                'stock_qty' => 12,
                'low_stock_threshold' => 4,
                'allow_backorder' => true,
                'attributes' => [
                    ['attribute_name' => 'color', 'attribute_value' => 'red', 'sort_order' => 0],
                    ['attribute_name' => 'size', 'attribute_value' => 'XL', 'sort_order' => 1],
                ],
            ]],
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
        ]), $seller);

        $product->refresh();
        $variant = $product->variants()->firstOrFail();

        $this->assertSame('Direct Variant Title', $variant->title);
        $this->assertSame('987654321', $variant->barcode);
        $this->assertSame(12, $variant->stock_qty);
        $this->assertSame(0, $product->revisions()->where('status', ProductRevisionStatus::PENDING)->count());
    }

    public function test_approved_variant_reviewable_update_creates_pending_revision_without_mutating_live_variant(): void
    {
        $seller = $this->seller();
        $admin = $this->admin();
        $store = $this->storeFor($seller);

        /** @var CreateProduct $create */
        $create = app(CreateProduct::class);
        $product = $create->execute($store, $this->productData(), $seller);

        /** @var ApproveProductRevision $approve */
        $approve = app(ApproveProductRevision::class);
        $approve->execute($product->revisions()->firstOrFail(), $admin);

        /** @var UpdateProduct $update */
        $update = app(UpdateProduct::class);
        $update->execute($product->fresh(), $this->partialProductData([
            'variants' => [[
                'title' => 'Red / XL',
                'option_summary' => 'Color: Red, Size: XL',
                'sku' => 'SKU-001-RED-XL',
                'barcode' => '123456789',
                'original_price' => 200,
                'currency' => 'EGP',
                'sort_order' => 0,
                'is_default' => true,
                'is_active' => true,
                'inventory_mode' => 'tracked',
                'stock_qty' => 8,
                'low_stock_threshold' => 2,
                'allow_backorder' => false,
                'attributes' => [
                    ['attribute_name' => 'color', 'attribute_value' => 'red', 'sort_order' => 0],
                    ['attribute_name' => 'size', 'attribute_value' => 'XL', 'sort_order' => 1],
                ],
            ]],
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
        ]), $seller);

        $product->refresh();
        $variant = $product->variants()->firstOrFail();
        $pendingRevision = $product->revisions()->where('status', ProductRevisionStatus::PENDING)->first();

        $this->assertNotNull($pendingRevision);
        $this->assertSame('Red / XL', $variant->title);
        $this->assertSame(110.0, (float) $variant->original_price);
        $this->assertSame(['variants'], $pendingRevision->review_fields);
        $this->assertSame(200.0, (float) data_get($pendingRevision->payload, 'variants.0.original_price'));
    }

    public function test_review_only_variant_update_keeps_live_variant_id_unchanged(): void
    {
        $seller = $this->seller();
        $admin = $this->admin();
        $store = $this->storeFor($seller);

        /** @var CreateProduct $create */
        $create = app(CreateProduct::class);
        $product = $create->execute($store, $this->productData(), $seller);

        /** @var ApproveProductRevision $approve */
        $approve = app(ApproveProductRevision::class);
        $approve->execute($product->revisions()->firstOrFail(), $admin);

        $originalVariantId = $product->fresh()->variants()->value('id');

        /** @var UpdateProduct $update */
        $update = app(UpdateProduct::class);
        $update->execute($product->fresh(), $this->partialProductData([
            'variants' => [[
                'title' => 'Red / XL',
                'option_summary' => 'Color: Red, Size: XL',
                'sku' => 'SKU-001-RED-XL',
                'barcode' => '123456789',
                'original_price' => 200,
                'currency' => 'EGP',
                'sort_order' => 0,
                'is_default' => true,
                'is_active' => true,
                'inventory_mode' => 'tracked',
                'stock_qty' => 8,
                'low_stock_threshold' => 2,
                'allow_backorder' => false,
                'attributes' => [
                    ['attribute_name' => 'color', 'attribute_value' => 'red', 'sort_order' => 0],
                    ['attribute_name' => 'size', 'attribute_value' => 'XL', 'sort_order' => 1],
                ],
            ]],
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
        ]), $seller);

        $product->refresh();
        $variant = $product->variants()->firstOrFail();
        $pendingRevision = $product->revisions()->where('status', ProductRevisionStatus::PENDING)->firstOrFail();

        $this->assertSame($originalVariantId, $variant->id);
        $this->assertSame(110.0, (float) $variant->original_price);
        $this->assertSame(['variants'], $pendingRevision->review_fields);
        $this->assertSame(200.0, (float) data_get($pendingRevision->payload, 'variants.0.original_price'));
    }

    public function test_approved_image_metadata_update_applies_immediately_without_creating_pending_revision(): void
    {
        $seller = $this->seller();
        $admin = $this->admin();
        $store = $this->storeFor($seller);

        /** @var CreateProduct $create */
        $create = app(CreateProduct::class);
        $product = $create->execute($store, $this->productData(), $seller);

        /** @var ApproveProductRevision $approve */
        $approve = app(ApproveProductRevision::class);
        $approve->execute($product->revisions()->firstOrFail(), $admin);

        $imagePath = $product->images()->value('image_url');

        /** @var UpdateProduct $update */
        $update = app(UpdateProduct::class);
        $update->execute($product->fresh(), $this->partialProductData([
            'images' => [[
                'image_url' => $imagePath,
                'sort_order' => 5,
                'is_primary' => true,
                'file' => null,
            ]],
        ]), $seller);

        $product->refresh();
        $image = $product->images()->firstOrFail();

        $this->assertSame(5, $image->sort_order);
        $this->assertTrue($image->is_primary);
        $this->assertSame(0, $product->revisions()->where('status', ProductRevisionStatus::PENDING)->count());
    }

    public function test_approved_image_file_change_creates_pending_revision_without_mutating_live_image(): void
    {
        $seller = $this->seller();
        $admin = $this->admin();
        $store = $this->storeFor($seller);

        /** @var CreateProduct $create */
        $create = app(CreateProduct::class);
        $product = $create->execute($store, $this->productData(), $seller);

        /** @var ApproveProductRevision $approve */
        $approve = app(ApproveProductRevision::class);
        $approve->execute($product->revisions()->firstOrFail(), $admin);

        $imagePath = $product->images()->value('image_url');

        /** @var UpdateProduct $update */
        $update = app(UpdateProduct::class);
        $update->execute($product->fresh(), $this->partialProductData([
            'images' => [[
                'image_url' => $imagePath,
                'sort_order' => 0,
                'is_primary' => true,
                'file' => UploadedFile::fake()->create('replacement.jpg', 100, 'image/jpeg'),
            ]],
        ]), $seller);

        $product->refresh();
        $pendingRevision = $product->revisions()->where('status', ProductRevisionStatus::PENDING)->first();

        $this->assertNotNull($pendingRevision);
        $this->assertSame($imagePath, $product->images()->value('image_url'));
        $this->assertSame(['images'], $pendingRevision->review_fields);
        $this->assertNotSame($imagePath, data_get($pendingRevision->payload, 'images.0.image_url'));
    }

    public function test_approved_mixed_image_update_applies_metadata_live_and_keeps_file_pending(): void
    {
        $seller = $this->seller();
        $admin = $this->admin();
        $store = $this->storeFor($seller);

        /** @var CreateProduct $create */
        $create = app(CreateProduct::class);
        $product = $create->execute($store, $this->productData(), $seller);

        /** @var ApproveProductRevision $approve */
        $approve = app(ApproveProductRevision::class);
        $approve->execute($product->revisions()->firstOrFail(), $admin);

        $imagePath = $product->images()->value('image_url');

        /** @var UpdateProduct $update */
        $update = app(UpdateProduct::class);
        $update->execute($product->fresh(), $this->partialProductData([
            'images' => [[
                'image_url' => $imagePath,
                'sort_order' => 5,
                'is_primary' => true,
                'file' => UploadedFile::fake()->create('replacement.jpg', 100, 'image/jpeg'),
            ]],
        ]), $seller);

        $product->refresh();
        $image = $product->images()->firstOrFail();
        $pendingRevision = $product->revisions()->where('status', ProductRevisionStatus::PENDING)->firstOrFail();

        $this->assertSame(5, $image->sort_order);
        $this->assertTrue($image->is_primary);
        $this->assertSame($imagePath, $image->image_url);
        $this->assertSame(['images'], $pendingRevision->review_fields);
        $this->assertNotSame($imagePath, data_get($pendingRevision->payload, 'images.0.image_url'));
    }

    public function test_approved_mixed_update_applies_direct_fields_and_keeps_reviewable_fields_pending(): void
    {
        $seller = $this->seller();
        $admin = $this->admin();
        $store = $this->storeFor($seller);

        /** @var CreateProduct $create */
        $create = app(CreateProduct::class);
        $product = $create->execute($store, $this->productData(), $seller);

        /** @var ApproveProductRevision $approve */
        $approve = app(ApproveProductRevision::class);
        $approve->execute($product->revisions()->firstOrFail(), $admin);

        /** @var UpdateProduct $update */
        $update = app(UpdateProduct::class);
        $update->execute($product->fresh(), $this->partialProductData([
            'sku' => 'SKU-002',
            'is_featured' => true,
        ]), $seller);

        $product->refresh();
        $pendingRevision = $product->revisions()->where('status', ProductRevisionStatus::PENDING)->first();

        $this->assertNotNull($pendingRevision);
        $this->assertTrue($product->is_featured);
        $this->assertSame('Example Product', $product->title);
        $this->assertSame('SKU-001', $product->sku);
        $this->assertSame(['sku'], $pendingRevision->review_fields);
        $this->assertSame('SKU-002', data_get($pendingRevision->payload, 'product.sku'));
        $this->assertTrue(data_get($pendingRevision->payload, 'product.is_featured'));
    }

    public function test_pending_revision_review_fields_are_merged_on_subsequent_updates(): void
    {
        $seller = $this->seller();
        $admin = $this->admin();
        $store = $this->storeFor($seller);

        /** @var CreateProduct $create */
        $create = app(CreateProduct::class);
        $product = $create->execute($store, $this->productData(), $seller);

        /** @var ApproveProductRevision $approve */
        $approve = app(ApproveProductRevision::class);
        $approve->execute($product->revisions()->firstOrFail(), $admin);

        /** @var UpdateProduct $update */
        $update = app(UpdateProduct::class);
        $update->execute($product->fresh(), $this->partialProductData([
            'short_description' => 'Edited pending short description',
        ]), $seller);

        $pendingRevision = $product->fresh()->revisions()->where('status', ProductRevisionStatus::PENDING)->firstOrFail();

        $update->execute($product->fresh(), $this->partialProductData([
            'offer' => [
                'type' => 'percentage',
                'status' => 'active',
                'label' => 'Pending percentage offer',
                'claim_expiration_minutes' => 720,
                'percentage_value' => 20,
                'max_discount' => 50,
                'fixed_amount' => null,
                'buy_qty' => null,
                'get_qty' => null,
                'allow_mix_buy_variants' => false,
                'allow_mix_reward_variants' => false,
                'buy_variant_skus' => [],
                'reward_variant_skus' => [],
            ],
        ]), $seller);

        $product->refresh();
        $pendingRevisions = $product->revisions()->where('status', ProductRevisionStatus::PENDING)->get();
        $updatedPendingRevision = $pendingRevisions->first();

        $this->assertCount(1, $pendingRevisions);
        $this->assertNotNull($updatedPendingRevision);
        $this->assertSame($pendingRevision->id, $updatedPendingRevision->id);
        $this->assertSame(['short_description', 'offer'], $updatedPendingRevision->review_fields);
        $this->assertSame('Edited pending short description', data_get($updatedPendingRevision->payload, 'product.short_description'));
        $this->assertSame('percentage', data_get($updatedPendingRevision->payload, 'offer.type'));
        $this->assertSame(2, $updatedPendingRevision->revision_no);
        $this->assertSame(1, $updatedPendingRevision->base_revision_no);
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

    private function productData(array $overrides = []): ProductData
    {
        $partial = (bool) ($overrides['partial'] ?? false);
        $attributes = array_replace([
            'title' => 'Example Product',
            'slug' => 'example-product',
            'short_description' => 'Short description',
            'description' => 'Long description',
            'currency' => 'EGP',
            'sku' => 'SKU-001',
            'is_featured' => false,
        ], array_intersect_key($overrides, array_flip([
            'title',
            'slug',
            'short_description',
            'description',
            'currency',
            'sku',
            'is_featured',
        ])));

        if ($partial) {
            $attributes = array_intersect_key($attributes, array_intersect_key($overrides, array_flip([
                'title',
                'slug',
                'short_description',
                'description',
                'currency',
                'sku',
                'is_featured',
            ])));
        }

        $images = $overrides['images'] ?? [[
            'file' => UploadedFile::fake()->create('product.jpg', 100, 'image/jpeg'),
            'sort_order' => 0,
            'is_primary' => true,
        ]];

        $variants = $overrides['variants'] ?? [[
            'title' => 'Red / XL',
            'option_summary' => 'Color: Red, Size: XL',
            'sku' => 'SKU-001-RED-XL',
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
                ['attribute_name' => 'color', 'attribute_value' => 'red', 'sort_order' => 0],
                ['attribute_name' => 'size', 'attribute_value' => 'XL', 'sort_order' => 1],
            ],
        ]];

        $offer = $overrides['offer'] ?? [
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
        ];

        return new ProductData(
            attributes: $attributes,
            categoryIds: $overrides['category_ids'] ?? [],
            images: $images,
            variants: $variants,
            offer: $offer,
            hasCategoryIds: array_key_exists('category_ids', $overrides),
            hasImages: array_key_exists('images', $overrides) || ! array_key_exists('hasImages', $overrides),
            hasVariants: array_key_exists('variants', $overrides) || ! array_key_exists('hasVariants', $overrides),
            hasOffer: array_key_exists('offer', $overrides) || ! array_key_exists('hasOffer', $overrides),
        );
    }

    private function partialProductData(array $overrides = []): ProductData
    {
        return $this->productData([
            ...$overrides,
            'partial' => true,
            'hasImages' => array_key_exists('images', $overrides),
            'hasVariants' => array_key_exists('variants', $overrides),
            'hasOffer' => array_key_exists('offer', $overrides),
        ]);
    }
}
