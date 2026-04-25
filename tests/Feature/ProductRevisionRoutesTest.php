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

        Storage::persistentFake('public');
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
            ->assertJsonPath('data.0.status', ProductRevisionStatus::PENDING->value)
            ->assertJsonPath('data.0.review_fields', [])
            ->assertJsonPath('data.0.requested_changes', []);

        $this->actingAs($seller, 'sanctum')
            ->getJson("/api/v1/stores/{$store->id}/products/{$productId}/revisions/{$revisionId}")
            ->assertOk()
            ->assertJsonPath('data.id', $revisionId)
            ->assertJsonPath('data.payload.product.title', 'Revision Product')
            ->assertJsonPath('data.review_fields', [])
            ->assertJsonPath('data.requested_changes', []);
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

    public function test_admin_can_reject_pending_revision_with_variant_requested_change_without_changing_live_product(): void
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
                'requested_changes' => [
                    [
                        'section' => 'variants',
                        'selector' => [
                            'sku' => 'SKU-REV-RED-XL',
                        ],
                        'field' => 'original_price',
                        'path' => 'variants[sku=SKU-REV-RED-XL].original_price',
                        'label' => 'Variant price',
                        'message' => 'Fix this price',
                    ],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('data.status', ProductRevisionStatus::REJECTED->value)
            ->assertJsonPath('data.rejection_reason', 'Offer details need changes')
            ->assertJsonPath('data.requested_changes.0.section', 'variants')
            ->assertJsonPath('data.requested_changes.0.selector.sku', 'SKU-REV-RED-XL')
            ->assertJsonPath('data.requested_changes.0.field', 'original_price')
            ->assertJsonPath('data.requested_changes.0.path', 'variants[sku=SKU-REV-RED-XL].original_price')
            ->assertJsonPath('data.requested_changes.0.label', 'Variant price')
            ->assertJsonPath('data.requested_changes.0.message', 'Fix this price')
            ->assertJsonPath('data.review_fields', []);

        $this->assertDatabaseHas('products', [
            'id' => $productId,
            'title' => 'Revision Product',
            'status' => 'inactive',
        ]);

        $revision = ProductRevision::query()->findOrFail($revisionId);
        $this->assertEquals([
            [
                'section' => 'variants',
                'selector' => [
                    'sku' => 'SKU-REV-RED-XL',
                ],
                'field' => 'original_price',
                'path' => 'variants[sku=SKU-REV-RED-XL].original_price',
                'label' => 'Variant price',
                'message' => 'Fix this price',
            ],
        ], $revision->requested_changes);
    }

    public function test_admin_can_reject_revision_with_variant_attribute_requested_change(): void
    {
        $seller = $this->seller();
        $admin = $this->admin();
        $store = $this->storeFor($seller);

        $createResponse = $this->actingAs($seller, 'sanctum')
            ->postJson("/api/v1/stores/{$store->id}/products", $this->payload());

        $revisionId = ProductRevision::query()->where('product_id', $createResponse->json('data.id'))->value('id');

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/admin/products/revisions/{$revisionId}/reject", [
                'reason' => 'Needs changes',
                'requested_changes' => [
                    [
                        'section' => 'variant_attributes',
                        'variant_selector' => [
                            'sku' => 'SKU-REV-RED-XL',
                        ],
                        'attribute_selector' => [
                            'name' => 'color',
                        ],
                        'field' => 'attribute_value',
                        'path' => 'variants[sku=SKU-REV-RED-XL].attributes[name=color].attribute_value',
                        'label' => 'Color',
                        'message' => 'Fix color value',
                    ],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('data.requested_changes.0.section', 'variant_attributes')
            ->assertJsonPath('data.requested_changes.0.variant_selector.sku', 'SKU-REV-RED-XL')
            ->assertJsonPath('data.requested_changes.0.attribute_selector.name', 'color')
            ->assertJsonPath('data.requested_changes.0.field', 'attribute_value');
    }

    public function test_admin_can_reject_revision_with_image_requested_change(): void
    {
        $seller = $this->seller();
        $admin = $this->admin();
        $store = $this->storeFor($seller);

        $createResponse = $this->actingAs($seller, 'sanctum')
            ->postJson("/api/v1/stores/{$store->id}/products", $this->payload());

        $revisionId = ProductRevision::query()->where('product_id', $createResponse->json('data.id'))->value('id');

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/admin/products/revisions/{$revisionId}/reject", [
                'reason' => 'Needs changes',
                'requested_changes' => [
                    [
                        'section' => 'images',
                        'selector' => [
                            'image_url' => 'products/1/images/test.jpg',
                        ],
                        'field' => 'file',
                        'path' => 'images[image_url=products/1/images/test.jpg].file',
                        'label' => 'Image',
                        'message' => 'Upload clearer image',
                    ],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('data.requested_changes.0.section', 'images')
            ->assertJsonPath('data.requested_changes.0.selector.image_url', 'products/1/images/test.jpg')
            ->assertJsonPath('data.requested_changes.0.field', 'file');
    }

    public function test_admin_reject_validation_rejects_invalid_field_for_section(): void
    {
        $seller = $this->seller();
        $admin = $this->admin();
        $store = $this->storeFor($seller);

        $createResponse = $this->actingAs($seller, 'sanctum')
            ->postJson("/api/v1/stores/{$store->id}/products", $this->payload());

        $revisionId = ProductRevision::query()->where('product_id', $createResponse->json('data.id'))->value('id');

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/admin/products/revisions/{$revisionId}/reject", [
                'reason' => 'Needs changes',
                'requested_changes' => [
                    [
                        'section' => 'variants',
                        'selector' => [
                            'sku' => 'SKU-REV-RED-XL',
                        ],
                        'field' => 'stock_qty',
                        'message' => 'Not allowed',
                    ],
                ],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['requested_changes.0.field']);
    }

    public function test_admin_reject_validation_rejects_direct_only_image_field(): void
    {
        $seller = $this->seller();
        $admin = $this->admin();
        $store = $this->storeFor($seller);

        $createResponse = $this->actingAs($seller, 'sanctum')
            ->postJson("/api/v1/stores/{$store->id}/products", $this->payload());

        $revisionId = ProductRevision::query()->where('product_id', $createResponse->json('data.id'))->value('id');

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/admin/products/revisions/{$revisionId}/reject", [
                'reason' => 'Needs changes',
                'requested_changes' => [
                    [
                        'section' => 'images',
                        'selector' => [
                            'image_url' => 'products/1/images/test.jpg',
                        ],
                        'field' => 'sort_order',
                        'message' => 'Not allowed',
                    ],
                ],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['requested_changes.0.field']);
    }

    public function test_admin_reject_validation_rejects_variant_requested_change_without_selector(): void
    {
        $seller = $this->seller();
        $admin = $this->admin();
        $store = $this->storeFor($seller);

        $createResponse = $this->actingAs($seller, 'sanctum')
            ->postJson("/api/v1/stores/{$store->id}/products", $this->payload());

        $revisionId = ProductRevision::query()->where('product_id', $createResponse->json('data.id'))->value('id');

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/admin/products/revisions/{$revisionId}/reject", [
                'reason' => 'Needs changes',
                'requested_changes' => [
                    [
                        'section' => 'variants',
                        'field' => 'original_price',
                        'message' => 'Selector missing',
                    ],
                ],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['requested_changes.0.selector']);
    }

    public function test_admin_reject_validation_rejects_variant_attribute_requested_change_without_required_selectors(): void
    {
        $seller = $this->seller();
        $admin = $this->admin();
        $store = $this->storeFor($seller);

        $createResponse = $this->actingAs($seller, 'sanctum')
            ->postJson("/api/v1/stores/{$store->id}/products", $this->payload());

        $revisionId = ProductRevision::query()->where('product_id', $createResponse->json('data.id'))->value('id');

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/admin/products/revisions/{$revisionId}/reject", [
                'reason' => 'Needs changes',
                'requested_changes' => [
                    [
                        'section' => 'variant_attributes',
                        'field' => 'attribute_value',
                        'message' => 'Selectors missing',
                    ],
                ],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'requested_changes.0.variant_selector',
                'requested_changes.0.attribute_selector',
            ]);
    }

    public function test_admin_reject_validation_rejects_title_as_requested_change_section(): void
    {
        $seller = $this->seller();
        $admin = $this->admin();
        $store = $this->storeFor($seller);

        $createResponse = $this->actingAs($seller, 'sanctum')
            ->postJson("/api/v1/stores/{$store->id}/products", $this->payload());

        $revisionId = ProductRevision::query()->where('product_id', $createResponse->json('data.id'))->value('id');

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/admin/products/revisions/{$revisionId}/reject", [
                'reason' => 'Needs changes',
                'requested_changes' => [
                    [
                        'section' => 'title',
                        'field' => 'value',
                        'message' => 'Not allowed',
                    ],
                ],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['requested_changes.0.section']);
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
            ->putJson("/api/v1/stores/{$store->id}/products/{$productId}", [
                'short_description' => 'Pending edited short description',
            ]);

        $updateResponse->assertOk()
            ->assertJsonPath('data.title', 'Revision Product')
            ->assertJsonPath('data.approval_status', ProductApprovalStatus::APPROVED->value)
            ->assertJsonPath('data.pending_revision.status', ProductRevisionStatus::PENDING->value)
            ->assertJsonPath('data.pending_revision.payload.product.short_description', 'Pending edited short description')
            ->assertJsonPath('data.pending_revision.review_fields', ['short_description']);

        $this->actingAs($seller, 'sanctum')
            ->getJson("/api/v1/stores/{$store->id}/products/{$productId}")
            ->assertOk()
            ->assertJsonPath('data.title', 'Revision Product')
            ->assertJsonPath('data.pending_revision.payload.product.short_description', 'Pending edited short description')
            ->assertJsonPath('data.pending_revision.review_fields', ['short_description']);

        $pendingRevision = ProductRevision::query()
            ->where('product_id', $productId)
            ->where('status', ProductRevisionStatus::PENDING)
            ->first();

        $this->assertNotNull($pendingRevision);
        $this->assertSame('Pending edited short description', data_get($pendingRevision->payload, 'product.short_description'));
        $this->assertSame(['short_description'], $pendingRevision->review_fields);
        $this->assertIsArray(data_get($pendingRevision->payload, 'product'));
        $this->assertIsArray(data_get($pendingRevision->payload, 'images'));
        $this->assertIsArray(data_get($pendingRevision->payload, 'variants'));
        $this->assertArrayHasKey('offer', $pendingRevision->payload);
        $this->assertDatabaseHas('products', [
            'id' => $productId,
            'title' => 'Revision Product',
            'short_description' => 'Short description',
            'published_revision_no' => 1,
        ]);
    }

    public function test_seller_index_includes_requested_changes_from_latest_rejected_revision(): void
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
                'reason' => 'Needs changes',
                'notes' => 'Please fix the pricing',
                'requested_changes' => [
                    [
                        'section' => 'variants',
                        'selector' => [
                            'sku' => 'SKU-REV-RED-XL',
                        ],
                        'field' => 'original_price',
                        'path' => 'variants[sku=SKU-REV-RED-XL].original_price',
                        'label' => 'Variant price',
                        'message' => 'Fix this price',
                    ],
                ],
            ])
            ->assertOk();

        $this->actingAs($seller, 'sanctum')
            ->getJson("/api/v1/stores/{$store->id}/products")
            ->assertOk()
            ->assertJsonPath('data.0.id', $productId)
            ->assertJsonPath('data.0.requested_changes.status', ProductRevisionStatus::REJECTED->value)
            ->assertJsonPath('data.0.requested_changes.requested_changes.0.section', 'variants')
            ->assertJsonPath('data.0.requested_changes.requested_changes.0.selector.sku', 'SKU-REV-RED-XL')
            ->assertJsonPath('data.0.requested_changes.requested_changes.0.field', 'original_price');
    }

    public function test_seller_update_with_direct_only_field_updates_live_product_without_pending_revision(): void
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

        $originalVariantId = Product::query()->findOrFail($productId)->variants()->value('id');

        $this->actingAs($seller, 'sanctum')
            ->putJson("/api/v1/stores/{$store->id}/products/{$productId}", [
                'is_featured' => true,
            ])
            ->assertOk()
            ->assertJsonPath('data.is_featured', true)
            ->assertJsonPath('data.pending_revision', null);

        $this->assertDatabaseHas('products', [
            'id' => $productId,
            'is_featured' => true,
        ]);
        $this->assertDatabaseMissing('product_revisions', [
            'product_id' => $productId,
            'status' => ProductRevisionStatus::PENDING->value,
        ]);
    }

    public function test_approved_top_level_direct_fields_update_live_without_pending_revision(): void
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

        $originalVariantId = Product::query()->findOrFail($productId)->variants()->value('id');

        $this->actingAs($seller, 'sanctum')
            ->putJson("/api/v1/stores/{$store->id}/products/{$productId}", [
                'title' => 'Directly Updated Title',
                'description' => 'Directly updated description',
            ])
            ->assertOk()
            ->assertJsonPath('data.title', 'Directly Updated Title')
            ->assertJsonPath('data.description', 'Directly updated description')
            ->assertJsonPath('data.pending_revision', null);

        $this->assertDatabaseHas('products', [
            'id' => $productId,
            'title' => 'Directly Updated Title',
            'description' => 'Directly updated description',
        ]);
    }

    public function test_approved_variant_direct_only_fields_update_live_without_pending_revision(): void
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

        $originalVariantId = Product::query()->findOrFail($productId)->variants()->value('id');

        $this->actingAs($seller, 'sanctum')
            ->putJson("/api/v1/stores/{$store->id}/products/{$productId}", [
                'variants' => [
                    [
                        'title' => 'Direct Variant Title',
                        'option_summary' => 'Color: Red, Size: XL',
                        'sku' => 'SKU-REV-RED-XL',
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
            ])
            ->assertOk()
            ->assertJsonPath('data.variants.0.title', 'Direct Variant Title')
            ->assertJsonPath('data.variants.0.barcode', '987654321')
            ->assertJsonPath('data.variants.0.stock_qty', 12)
            ->assertJsonPath('data.pending_revision', null);

        $this->assertDatabaseHas('product_variants', [
            'product_id' => $productId,
            'title' => 'Direct Variant Title',
            'barcode' => '987654321',
            'stock_qty' => 12,
            'low_stock_threshold' => 4,
            'allow_backorder' => true,
            'is_active' => false,
        ]);
    }

    public function test_approved_variant_reviewable_field_creates_pending_revision(): void
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

        $originalVariantId = Product::query()->findOrFail($productId)->variants()->value('id');

        $this->actingAs($seller, 'sanctum')
            ->putJson("/api/v1/stores/{$store->id}/products/{$productId}", [
                'variants' => [
                    [
                        'title' => 'Red / XL',
                        'option_summary' => 'Color: Red, Size: XL',
                        'sku' => 'SKU-REV-RED-XL',
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
            ])
            ->assertOk()
            ->assertJsonPath('data.variants.0.original_price', '110.00')
            ->assertJsonPath('data.pending_revision.review_fields', ['variants'])
            ->assertJsonPath('data.pending_revision.payload.variants.0.original_price', '200.00');

        $this->assertSame(
            $originalVariantId,
            Product::query()->findOrFail($productId)->variants()->value('id')
        );
    }

    public function test_approved_variant_mixed_fields_apply_direct_values_live_and_reviewable_values_to_revision(): void
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

        $this->actingAs($seller, 'sanctum')
            ->putJson("/api/v1/stores/{$store->id}/products/{$productId}", [
                'variants' => [
                    [
                        'title' => 'Direct Variant Title',
                        'option_summary' => 'Color: Red, Size: XL',
                        'sku' => 'SKU-REV-RED-XL',
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
            ])
            ->assertOk()
            ->assertJsonPath('data.variants.0.title', 'Direct Variant Title')
            ->assertJsonPath('data.variants.0.original_price', '110.00')
            ->assertJsonPath('data.pending_revision.review_fields', ['variants'])
            ->assertJsonPath('data.pending_revision.payload.variants.0.title', 'Direct Variant Title')
            ->assertJsonPath('data.pending_revision.payload.variants.0.original_price', '200.00');
    }

    public function test_approved_image_metadata_updates_live_without_pending_revision(): void
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

        $imagePath = Product::query()->findOrFail($productId)->images()->value('image_url');

        $this->actingAs($seller, 'sanctum')
            ->putJson("/api/v1/stores/{$store->id}/products/{$productId}", [
                'images' => [
                    [
                        'image_url' => $imagePath,
                        'sort_order' => 5,
                        'is_primary' => true,
                    ],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('data.images.0.sort_order', 5)
            ->assertJsonPath('data.images.0.is_primary', true)
            ->assertJsonPath('data.pending_revision', null);
    }

    public function test_approved_image_file_change_creates_pending_revision(): void
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

        $imagePath = Product::query()->findOrFail($productId)->images()->value('image_url');

        $this->actingAs($seller, 'sanctum')
            ->put("/api/v1/stores/{$store->id}/products/{$productId}", [
                'images' => [
                    [
                        'image_url' => $imagePath,
                        'file' => UploadedFile::fake()->create('replacement.jpg', 100, 'image/jpeg'),
                        'sort_order' => 0,
                        'is_primary' => true,
                    ],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('data.images.0.path', $imagePath)
            ->assertJsonPath('data.pending_revision.review_fields', ['images']);

        $pendingRevision = ProductRevision::query()
            ->where('product_id', $productId)
            ->where('status', ProductRevisionStatus::PENDING)
            ->firstOrFail();

        $this->assertNotSame($imagePath, data_get($pendingRevision->payload, 'images.0.image_url'));
    }

    public function test_approved_mixed_image_update_applies_metadata_live_and_keeps_file_pending(): void
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

        $imagePath = Product::query()->findOrFail($productId)->images()->value('image_url');

        $this->actingAs($seller, 'sanctum')
            ->put("/api/v1/stores/{$store->id}/products/{$productId}", [
                'images' => [
                    [
                        'image_url' => $imagePath,
                        'file' => UploadedFile::fake()->create('replacement.jpg', 100, 'image/jpeg'),
                        'sort_order' => 5,
                        'is_primary' => true,
                    ],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('data.images.0.path', $imagePath)
            ->assertJsonPath('data.images.0.sort_order', 5)
            ->assertJsonPath('data.images.0.is_primary', true)
            ->assertJsonPath('data.pending_revision.review_fields', ['images']);

        $pendingRevision = ProductRevision::query()
            ->where('product_id', $productId)
            ->where('status', ProductRevisionStatus::PENDING)
            ->firstOrFail();

        $this->assertNotSame($imagePath, data_get($pendingRevision->payload, 'images.0.image_url'));
    }

    public function test_seller_update_with_mixed_fields_updates_direct_field_and_creates_review_revision(): void
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

        $this->actingAs($seller, 'sanctum')
            ->putJson("/api/v1/stores/{$store->id}/products/{$productId}", [
                'sku' => 'SKU-REV-002',
                'is_featured' => true,
            ])
            ->assertOk()
            ->assertJsonPath('data.title', 'Revision Product')
            ->assertJsonPath('data.is_featured', true)
            ->assertJsonPath('data.pending_revision.review_fields', ['sku'])
            ->assertJsonPath('data.pending_revision.payload.product.sku', 'SKU-REV-002');

        $product = Product::query()->findOrFail($productId);
        $pendingRevision = ProductRevision::query()
            ->where('product_id', $productId)
            ->where('status', ProductRevisionStatus::PENDING)
            ->first();

        $this->assertNotNull($pendingRevision);
        $this->assertTrue($product->is_featured);
        $this->assertSame('Revision Product', $product->title);
        $this->assertSame('SKU-REV-001', $product->sku);
        $this->assertSame(['sku'], $pendingRevision->review_fields);
    }

    public function test_slug_update_does_not_apply_directly_when_unclassified(): void
    {
        $seller = $this->seller();
        $admin = $this->admin();
        $store = $this->storeFor($seller);

        $createResponse = $this->actingAs($seller, 'sanctum')
            ->postJson("/api/v1/stores/{$store->id}/products", $this->payload([
                'slug' => 'manual-slug',
                'images' => [],
            ]));

        $productId = $createResponse->json('data.id');
        $initialRevisionId = ProductRevision::query()->where('product_id', $productId)->value('id');

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/admin/products/revisions/{$initialRevisionId}/approve");

        $response = $this->actingAs($seller, 'sanctum')
            ->putJson("/api/v1/stores/{$store->id}/products/{$productId}", [
                'slug' => 'changed-slug',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.slug', 'manual-slug')
            ->assertJsonPath('data.pending_revision', null);

        $this->assertDatabaseHas('products', [
            'id' => $productId,
            'slug' => 'manual-slug',
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
