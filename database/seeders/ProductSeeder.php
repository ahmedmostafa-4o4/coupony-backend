<?php

namespace Database\Seeders;

use App\Domain\Product\Enums\ProductStatus;
use App\Domain\Product\Enums\ProductType;
use App\Domain\Product\Models\Category;
use App\Domain\Product\Models\Product;
use App\Domain\Product\Models\ProductImage;
use App\Domain\Product\Models\ProductVariant;
use App\Domain\Store\Enums\StoreStatus;
use App\Domain\Store\Models\Store;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
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
            DB::transaction(function () use ($store, $categoryIds, &$seededProducts) {
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
                        'product_type' => $template['product_type'],
                        'base_price' => $template['base_price'],
                        'compare_at_price' => $template['compare_at_price'],
                        'currency' => 'EGP',
                        'sku' => $template['sku'],
                        'status' => $template['status'],
                        'is_featured' => $template['is_featured'],
                        'sale_count' => $template['sale_count'],
                        'redemption_count' => $template['redemption_count'],
                    ]);
                    $product->save();

                    $product->categories()->sync(
                        collect($template['category_slugs'])
                            ->map(fn(string $slug) => $categoryIds->get($slug))
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
                'product_type' => ProductType::STANDARD,
                'base_price' => 1499.00,
                'compare_at_price' => 1799.00,
                'sku' => 'PRD-EARBUDS-PRO',
                'status' => ProductStatus::ACTIVE,
                'is_featured' => true,
                'sale_count' => 34,
                'redemption_count' => 0,
                'category_slugs' => ['electronics', 'audio'],
                'images' => [
                    'products/seed/wireless-earbuds-pro-1.webp',
                    'products/seed/wireless-earbuds-pro-2.webp',
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
                'product_type' => ProductType::STANDARD,
                'base_price' => 2199.00,
                'compare_at_price' => 2499.00,
                'sku' => 'PRD-RUNNER-SNK',
                'status' => ProductStatus::ACTIVE,
                'is_featured' => false,
                'sale_count' => 21,
                'redemption_count' => 0,
                'category_slugs' => ['fashion', 'footwear'],
                'images' => [
                    'products/seed/performance-runner-sneakers-1.webp',
                    'products/seed/performance-runner-sneakers-2.webp',
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
                'product_type' => ProductType::COUPONABLE_ITEM,
                'base_price' => 399.00,
                'compare_at_price' => 500.00,
                'sku' => 'PRD-BRUNCH-VOUCHER',
                'status' => ProductStatus::ACTIVE,
                'is_featured' => true,
                'sale_count' => 48,
                'redemption_count' => 19,
                'category_slugs' => ['food-beverages', 'restaurant-deals', 'coffee-desserts'],
                'images' => [
                    'products/seed/weekend-brunch-voucher-1.webp',
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
                'product_type' => ProductType::SERVICE,
                'base_price' => 899.00,
                'compare_at_price' => 1099.00,
                'sku' => 'PRD-DEEP-CLEAN',
                'status' => ProductStatus::DRAFT,
                'is_featured' => false,
                'sale_count' => 0,
                'redemption_count' => 0,
                'category_slugs' => ['home-garden', 'kitchen-essentials'],
                'images' => [
                    'products/seed/deep-clean-home-service-1.webp',
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
                'product_type' => ProductType::STANDARD,
                'base_price' => 749.00,
                'compare_at_price' => 899.00,
                'sku' => 'PRD-SKINCARE-SET',
                'status' => ProductStatus::INACTIVE,
                'is_featured' => false,
                'sale_count' => 7,
                'redemption_count' => 0,
                'category_slugs' => ['beauty-health', 'skincare'],
                'images' => [
                    'products/seed/hydration-skincare-set-1.webp',
                    'products/seed/hydration-skincare-set-2.webp',
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
}
