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
use App\Domain\Product\Models\Product;
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

    public function test_update_after_approval_creates_or_updates_single_pending_revision_without_mutating_live(): void
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
        $update->execute($product->fresh(), $this->productData([
            'title' => 'Edited Pending Title',
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

        $update->execute($product->fresh(), $this->productData([
            'title' => 'Edited Pending Title v2',
            'hasOffer' => false,
            'images' => [],
            'hasImages' => false,
            'variants' => [],
            'hasVariants' => false,
        ]), $seller);

        $product->refresh();
        $pendingRevisions = $product->revisions()->where('status', ProductRevisionStatus::PENDING)->get();
        $pendingRevision = $pendingRevisions->first();

        $this->assertSame('Example Product', $product->title);
        $this->assertSame(ProductApprovalStatus::APPROVED->value, $product->approval_status->value);
        $this->assertCount(1, $pendingRevisions);
        $this->assertSame('Edited Pending Title v2', data_get($pendingRevision->payload, 'product.title'));
        $this->assertSame('percentage', data_get($pendingRevision->payload, 'offer.type'));
        $this->assertSame(2, $pendingRevision->revision_no);
        $this->assertSame(1, $pendingRevision->base_revision_no);
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
        $attributes = array_replace([
            'title' => 'Example Product',
            'slug' => 'example-product',
            'short_description' => 'Short description',
            'description' => 'Long description',
            'base_price' => 100,
            'compare_at_price' => 120,
            'currency' => 'EGP',
            'sku' => 'SKU-001',
            'is_featured' => false,
        ], array_intersect_key($overrides, array_flip([
            'title',
            'slug',
            'short_description',
            'description',
            'base_price',
            'compare_at_price',
            'currency',
            'sku',
            'is_featured',
        ])));

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
            hasImages: array_key_exists('images', $overrides) || !array_key_exists('hasImages', $overrides),
            hasVariants: array_key_exists('variants', $overrides) || !array_key_exists('hasVariants', $overrides),
            hasOffer: array_key_exists('offer', $overrides) || !array_key_exists('hasOffer', $overrides),
        );
    }
}
