<?php

namespace Database\Seeders;

use App\Domain\Product\Enums\ProductApprovalStatus;
use App\Domain\Product\Enums\ProductOfferStatus;
use App\Domain\Product\Enums\ProductOfferTargetRole;
use App\Domain\Product\Enums\ProductOfferType;
use App\Domain\Product\Enums\ProductStatus;
use App\Domain\Product\Models\Category;
use App\Domain\Product\Models\Product;
use App\Domain\Product\Models\ProductImage;
use App\Domain\Product\Models\ProductOffer;
use App\Domain\Product\Models\ProductVariant;
use App\Domain\Store\Enums\StoreStatus;
use App\Domain\Store\Models\Store;
use App\Domain\User\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $adminId = User::role('admin')->value('id');
        $stores = Store::query()
            ->where('status', StoreStatus::ACTIVE)
            ->orderBy('created_at')
            ->get();

        if ($stores->isEmpty()) {
            $this->command->warn('Please run StoreSeeder first!');

            return;
        }

        $categoryIds = Category::query()->pluck('id', 'slug');

        if ($categoryIds->isEmpty()) {
            $this->command->warn('Please run ProductCategorySeeder first!');

            return;
        }

        $seededProducts = 0;

        foreach ($stores as $store) {
            DB::transaction(function () use ($store, $categoryIds, $adminId, &$seededProducts) {
                foreach ($this->productTemplates() as $template) {
                    $product = Product::withTrashed()->firstOrNew([
                        'store_id' => $store->id,
                        'slug' => $template['slug'],
                    ]);

                    if ($product->trashed()) {
                        $product->restore();
                    }

                    $product->fill([
                        'title' => $template['title'],
                        'short_description' => $template['short_description'],
                        'description' => $template['description'],
                        'base_price' => $template['base_price'],
                        'compare_at_price' => $template['compare_at_price'],
                        'currency' => 'EGP',
                        'sku' => $template['sku'],
                        'status' => $template['status'],
                        'approval_status' => $template['approval_status'],
                        'published_revision_no' => $template['published_revision_no'],
                        'approved_at' => $template['approved_at'],
                        'approved_by' => $template['approved_by'] ?? $adminId,
                        'rejected_at' => $template['rejected_at'],
                        'rejected_by' => $template['rejected_by'],
                        'rejection_reason' => $template['rejection_reason'],
                        'admin_notes' => $template['admin_notes'],
                        'is_featured' => $template['is_featured'],
                        'sale_count' => $template['sale_count'],
                        'redemption_count' => $template['redemption_count'],
                    ]);
                    $product->save();

                    $product->categories()->sync(
                        collect($template['category_slugs'])
                            ->map(fn (string $slug) => $categoryIds->get($slug))
                            ->filter()
                            ->values()
                            ->all()
                    );

                    ProductImage::query()->where('product_id', $product->id)->delete();
                    foreach ($template['images'] as $index => $imagePath) {
                        ProductImage::create([
                            'product_id' => $product->id,
                            'image_url' => $imagePath,
                            'sort_order' => $index,
                            'is_primary' => $index === 0,
                            'created_at' => now(),
                        ]);
                    }

                    $this->syncVariants($product, $template['variants']);
                    $this->syncOffer($product, $template['offer']);
                    $seededProducts++;
                }
            });
        }

        $this->command->info("{$seededProducts} products seeded across {$stores->count()} stores");
    }

    private function syncVariants(Product $product, array $variants): void
    {
        $existingIds = [];

        foreach ($variants as $index => $variantData) {
            $variant = ProductVariant::withTrashed()->firstOrNew([
                'product_id' => $product->id,
                'sku' => $variantData['sku'],
            ]);

            if ($variant->trashed()) {
                $variant->restore();
            }

            $variant->fill([
                'title' => $variantData['title'],
                'option_summary' => $variantData['option_summary'],
                'barcode' => $variantData['barcode'],
                'price' => $variantData['price'],
                'compare_at_price' => $variantData['compare_at_price'],
                'currency' => 'EGP',
                'sort_order' => $index,
                'is_default' => $variantData['is_default'],
                'is_active' => $variantData['is_active'],
                'sale_count' => $variantData['sale_count'],
                'redemption_count' => $variantData['redemption_count'],
            ]);
            $variant->save();

            $existingIds[] = $variant->id;

            $variant->attributes()->delete();
            foreach ($variantData['attributes'] as $attributeIndex => $attribute) {
                $variant->attributes()->create([
                    'attribute_name' => $attribute['attribute_name'],
                    'attribute_value' => $attribute['attribute_value'],
                    'sort_order' => $attributeIndex,
                    'created_at' => now(),
                ]);
            }
        }

        ProductVariant::query()
            ->where('product_id', $product->id)
            ->whereNotIn('id', $existingIds)
            ->delete();
    }

    private function productTemplates(): array
    {
        return [
            [
                'title' => 'Wireless Earbuds Pro',
                'slug' => 'wireless-earbuds-pro',
                'short_description' => 'Premium wireless earbuds with noise isolation and fast charging.',
                'description' => 'A flagship audio accessory with balanced sound, low-latency pairing, and all-day comfort for commuting or workouts.',
                'base_price' => 1499.00,
                'compare_at_price' => 1799.00,
                'sku' => 'PRD-EARBUDS-PRO',
                'status' => ProductStatus::ACTIVE,
                'approval_status' => ProductApprovalStatus::APPROVED,
                'published_revision_no' => 1,
                'approved_at' => now()->subDays(20),
                'approved_by' => null,
                'rejected_at' => null,
                'rejected_by' => null,
                'rejection_reason' => null,
                'admin_notes' => 'Approved for marketplace listing.',
                'is_featured' => true,
                'sale_count' => 34,
                'redemption_count' => 0,
                'category_slugs' => ['electronics', 'audio'],
                'images' => [
                    'products/seed/wireless-earbuds-pro-1.webp',
                    'products/seed/wireless-earbuds-pro-2.webp',
                ],
                'offer' => [
                    'type' => ProductOfferType::PERCENTAGE,
                    'status' => ProductOfferStatus::ACTIVE,
                    'label' => '15% off launch offer',
                    'percentage_value' => 15,
                    'max_discount' => 250,
                    'claim_expiration_minutes' => 1440,
                ],
                'variants' => [
                    [
                        'title' => 'Black',
                        'option_summary' => 'Color: Black',
                        'sku' => 'VAR-EARBUDS-BLACK',
                        'barcode' => '100000001',
                        'price' => 1499.00,
                        'compare_at_price' => 1799.00,
                        'is_default' => true,
                        'is_active' => true,
                        'sale_count' => 18,
                        'redemption_count' => 0,
                        'attributes' => [
                            ['attribute_name' => 'color', 'attribute_value' => 'black'],
                        ],
                    ],
                    [
                        'title' => 'White',
                        'option_summary' => 'Color: White',
                        'sku' => 'VAR-EARBUDS-WHITE',
                        'barcode' => '100000002',
                        'price' => 1549.00,
                        'compare_at_price' => 1799.00,
                        'is_default' => false,
                        'is_active' => true,
                        'sale_count' => 16,
                        'redemption_count' => 0,
                        'attributes' => [
                            ['attribute_name' => 'color', 'attribute_value' => 'white'],
                        ],
                    ],
                ],
            ],
            [
                'title' => 'Performance Runner Sneakers',
                'slug' => 'performance-runner-sneakers',
                'short_description' => 'Lightweight running shoes built for daily training and long walks.',
                'description' => 'Breathable upper, cushioned sole, and durable grip make this pair a reliable option for active lifestyles.',
                'base_price' => 2199.00,
                'compare_at_price' => 2499.00,
                'sku' => 'PRD-RUNNER-SNK',
                'status' => ProductStatus::ACTIVE,
                'approval_status' => ProductApprovalStatus::APPROVED,
                'published_revision_no' => 1,
                'approved_at' => now()->subDays(15),
                'approved_by' => null,
                'rejected_at' => null,
                'rejected_by' => null,
                'rejection_reason' => null,
                'admin_notes' => 'Approved for marketplace listing.',
                'is_featured' => false,
                'sale_count' => 21,
                'redemption_count' => 0,
                'category_slugs' => ['fashion', 'footwear'],
                'images' => [
                    'products/seed/performance-runner-sneakers-1.webp',
                    'products/seed/performance-runner-sneakers-2.webp',
                ],
                'offer' => [
                    'type' => ProductOfferType::FIXED,
                    'status' => ProductOfferStatus::ACTIVE,
                    'label' => 'EGP 200 off',
                    'fixed_amount' => 200,
                    'claim_expiration_minutes' => 1440,
                ],
                'variants' => [
                    [
                        'title' => 'Blue / 42',
                        'option_summary' => 'Color: Blue, Size: 42',
                        'sku' => 'VAR-RUNNER-BLUE-42',
                        'barcode' => '100000003',
                        'price' => 2199.00,
                        'compare_at_price' => 2499.00,
                        'is_default' => true,
                        'is_active' => true,
                        'sale_count' => 12,
                        'redemption_count' => 0,
                        'attributes' => [
                            ['attribute_name' => 'color', 'attribute_value' => 'blue'],
                            ['attribute_name' => 'size', 'attribute_value' => '42'],
                        ],
                    ],
                    [
                        'title' => 'Black / 43',
                        'option_summary' => 'Color: Black, Size: 43',
                        'sku' => 'VAR-RUNNER-BLACK-43',
                        'barcode' => '100000004',
                        'price' => 2249.00,
                        'compare_at_price' => 2499.00,
                        'is_default' => false,
                        'is_active' => true,
                        'sale_count' => 9,
                        'redemption_count' => 0,
                        'attributes' => [
                            ['attribute_name' => 'color', 'attribute_value' => 'black'],
                            ['attribute_name' => 'size', 'attribute_value' => '43'],
                        ],
                    ],
                ],
            ],
            [
                'title' => 'Weekend Brunch Voucher',
                'slug' => 'weekend-brunch-voucher',
                'short_description' => 'Redeemable brunch deal for two with a fixed menu and drinks.',
                'description' => 'A couponable product that bundles breakfast favorites, hot drinks, and a dessert add-on for weekend visits.',
                'base_price' => 399.00,
                'compare_at_price' => 500.00,
                'sku' => 'PRD-BRUNCH-VOUCHER',
                'status' => ProductStatus::ACTIVE,
                'approval_status' => ProductApprovalStatus::APPROVED,
                'published_revision_no' => 1,
                'approved_at' => now()->subDays(10),
                'approved_by' => null,
                'rejected_at' => null,
                'rejected_by' => null,
                'rejection_reason' => null,
                'admin_notes' => 'Approved for marketplace listing.',
                'is_featured' => true,
                'sale_count' => 48,
                'redemption_count' => 19,
                'category_slugs' => ['food-beverages', 'restaurant-deals', 'coffee-desserts'],
                'images' => [
                    'products/seed/weekend-brunch-voucher-1.webp',
                ],
                'offer' => [
                    'type' => ProductOfferType::BUY_X_GET_Y,
                    'status' => ProductOfferStatus::ACTIVE,
                    'label' => 'Buy 2 get 1 voucher',
                    'buy_qty' => 2,
                    'get_qty' => 1,
                    'allow_mix_buy_variants' => false,
                    'allow_mix_reward_variants' => false,
                    'buy_variant_skus' => ['VAR-BRUNCH-2P'],
                    'reward_variant_skus' => ['VAR-BRUNCH-2P'],
                    'claim_expiration_minutes' => 1440,
                ],
                'variants' => [
                    [
                        'title' => 'For Two Guests',
                        'option_summary' => 'Package: Couple voucher',
                        'sku' => 'VAR-BRUNCH-2P',
                        'barcode' => '100000005',
                        'price' => 399.00,
                        'compare_at_price' => 500.00,
                        'is_default' => true,
                        'is_active' => true,
                        'sale_count' => 48,
                        'redemption_count' => 19,
                        'attributes' => [
                            ['attribute_name' => 'guests', 'attribute_value' => '2'],
                            ['attribute_name' => 'validity', 'attribute_value' => 'weekend'],
                        ],
                    ],
                ],
            ],
            [
                'title' => 'Deep Clean Home Service',
                'slug' => 'deep-clean-home-service',
                'short_description' => 'At-home deep cleaning package for apartments and small villas.',
                'description' => 'A service product covering kitchen, bathrooms, dusting, and floor cleaning with flexible scheduling.',
                'base_price' => 899.00,
                'compare_at_price' => 1099.00,
                'sku' => 'PRD-DEEP-CLEAN',
                'status' => ProductStatus::INACTIVE,
                'approval_status' => ProductApprovalStatus::PENDING,
                'published_revision_no' => 0,
                'approved_at' => null,
                'approved_by' => null,
                'rejected_at' => null,
                'rejected_by' => null,
                'rejection_reason' => null,
                'admin_notes' => 'Awaiting initial marketplace review.',
                'is_featured' => false,
                'sale_count' => 0,
                'redemption_count' => 0,
                'category_slugs' => ['home-garden', 'kitchen-essentials'],
                'images' => [
                    'products/seed/deep-clean-home-service-1.webp',
                ],
                'offer' => [
                    'type' => ProductOfferType::FIXED,
                    'status' => ProductOfferStatus::INACTIVE,
                    'label' => 'Draft service discount',
                    'fixed_amount' => 100,
                    'claim_expiration_minutes' => 1440,
                ],
                'variants' => [
                    [
                        'title' => 'Apartment Package',
                        'option_summary' => 'Coverage: Up to 180 sqm',
                        'sku' => 'VAR-DEEP-CLEAN-APT',
                        'barcode' => '100000006',
                        'price' => 899.00,
                        'compare_at_price' => 1099.00,
                        'is_default' => true,
                        'is_active' => true,
                        'sale_count' => 0,
                        'redemption_count' => 0,
                        'attributes' => [
                            ['attribute_name' => 'property_type', 'attribute_value' => 'apartment'],
                            ['attribute_name' => 'coverage', 'attribute_value' => '180 sqm'],
                        ],
                    ],
                ],
            ],
            [
                'title' => 'Hydration Skincare Set',
                'slug' => 'hydration-skincare-set',
                'short_description' => 'Three-step skincare kit focused on cleansing, hydration, and glow.',
                'description' => 'A curated beauty bundle that includes cleanser, serum, and moisturizer for daily use and gifting.',
                'base_price' => 749.00,
                'compare_at_price' => 899.00,
                'sku' => 'PRD-SKINCARE-SET',
                'status' => ProductStatus::INACTIVE,
                'approval_status' => ProductApprovalStatus::APPROVED,
                'published_revision_no' => 1,
                'approved_at' => now()->subDays(8),
                'approved_by' => null,
                'rejected_at' => null,
                'rejected_by' => null,
                'rejection_reason' => null,
                'admin_notes' => 'Approved but currently inactive.',
                'is_featured' => false,
                'sale_count' => 7,
                'redemption_count' => 0,
                'category_slugs' => ['beauty-health', 'skincare'],
                'images' => [
                    'products/seed/hydration-skincare-set-1.webp',
                    'products/seed/hydration-skincare-set-2.webp',
                ],
                'offer' => [
                    'type' => ProductOfferType::PERCENTAGE,
                    'status' => ProductOfferStatus::ACTIVE,
                    'label' => '10% beauty offer',
                    'percentage_value' => 10,
                    'max_discount' => 100,
                    'claim_expiration_minutes' => 1440,
                ],
                'variants' => [
                    [
                        'title' => 'Normal Skin',
                        'option_summary' => 'Skin type: Normal',
                        'sku' => 'VAR-SKINCARE-NORMAL',
                        'barcode' => '100000007',
                        'price' => 749.00,
                        'compare_at_price' => 899.00,
                        'is_default' => true,
                        'is_active' => true,
                        'sale_count' => 4,
                        'redemption_count' => 0,
                        'attributes' => [
                            ['attribute_name' => 'skin_type', 'attribute_value' => 'normal'],
                        ],
                    ],
                    [
                        'title' => 'Sensitive Skin',
                        'option_summary' => 'Skin type: Sensitive',
                        'sku' => 'VAR-SKINCARE-SENSITIVE',
                        'barcode' => '100000008',
                        'price' => 779.00,
                        'compare_at_price' => 899.00,
                        'is_default' => false,
                        'is_active' => true,
                        'sale_count' => 3,
                        'redemption_count' => 0,
                        'attributes' => [
                            ['attribute_name' => 'skin_type', 'attribute_value' => 'sensitive'],
                        ],
                    ],
                ],
            ],
        ];
    }

    private function syncOffer(Product $product, array $offerData): void
    {
        $offer = ProductOffer::query()->updateOrCreate(
            ['product_id' => $product->id],
            [
                'type' => $offerData['type'],
                'status' => $offerData['status'],
                'label' => $offerData['label'] ?? null,
                'starts_at' => $offerData['starts_at'] ?? null,
                'ends_at' => $offerData['ends_at'] ?? null,
                'claim_expiration_minutes' => $offerData['claim_expiration_minutes'] ?? null,
                'fixed_amount' => $offerData['fixed_amount'] ?? null,
                'percentage_value' => $offerData['percentage_value'] ?? null,
                'max_discount' => $offerData['max_discount'] ?? null,
                'buy_qty' => $offerData['buy_qty'] ?? null,
                'get_qty' => $offerData['get_qty'] ?? null,
                'allow_mix_buy_variants' => $offerData['allow_mix_buy_variants'] ?? false,
                'allow_mix_reward_variants' => $offerData['allow_mix_reward_variants'] ?? false,
            ]
        );

        $offer->targets()->delete();

        if ($offer->type !== ProductOfferType::BUY_X_GET_Y) {
            return;
        }

        $variantsBySku = $product->variants()
            ->get(['id', 'sku'])
            ->keyBy(fn (ProductVariant $variant) => mb_strtolower((string) $variant->sku));

        foreach ($offerData['buy_variant_skus'] ?? [] as $sku) {
            $variant = $variantsBySku->get(mb_strtolower((string) $sku));

            if ($variant) {
                $offer->targets()->create([
                    'variant_id' => $variant->id,
                    'role' => ProductOfferTargetRole::BUY,
                ]);
            }
        }

        foreach ($offerData['reward_variant_skus'] ?? [] as $sku) {
            $variant = $variantsBySku->get(mb_strtolower((string) $sku));

            if ($variant) {
                $offer->targets()->create([
                    'variant_id' => $variant->id,
                    'role' => ProductOfferTargetRole::REWARD,
                ]);
            }
        }
    }
}
