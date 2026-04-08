<?php

namespace Database\Seeders;

use App\Domain\Product\Models\Category;
use Illuminate\Database\Seeder;

class ProductCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Electronics',
                'slug' => 'electronics',
                'description' => 'Devices, accessories, and smart gadgets for daily use.',
                'sort_order' => 10,
                'children' => [
                    ['name' => 'Smartphones', 'slug' => 'smartphones', 'sort_order' => 11],
                    ['name' => 'Laptops', 'slug' => 'laptops', 'sort_order' => 12],
                    ['name' => 'Audio', 'slug' => 'audio', 'sort_order' => 13],
                ],
            ],
            [
                'name' => 'Fashion',
                'slug' => 'fashion',
                'description' => 'Clothing, footwear, and accessories for every season.',
                'sort_order' => 20,
                'children' => [
                    ['name' => 'Men Fashion', 'slug' => 'men-fashion', 'sort_order' => 21],
                    ['name' => 'Women Fashion', 'slug' => 'women-fashion', 'sort_order' => 22],
                    ['name' => 'Footwear', 'slug' => 'footwear', 'sort_order' => 23],
                ],
            ],
            [
                'name' => 'Food & Beverages',
                'slug' => 'food-beverages',
                'description' => 'Meal deals, grocery bundles, and cafe offers.',
                'sort_order' => 30,
                'children' => [
                    ['name' => 'Restaurant Deals', 'slug' => 'restaurant-deals', 'sort_order' => 31],
                    ['name' => 'Grocery Bundles', 'slug' => 'grocery-bundles', 'sort_order' => 32],
                    ['name' => 'Coffee & Desserts', 'slug' => 'coffee-desserts', 'sort_order' => 33],
                ],
            ],
            [
                'name' => 'Home & Garden',
                'slug' => 'home-garden',
                'description' => 'Home essentials, decor, and practical upgrades.',
                'sort_order' => 40,
                'children' => [
                    ['name' => 'Home Decor', 'slug' => 'home-decor', 'sort_order' => 41],
                    ['name' => 'Kitchen Essentials', 'slug' => 'kitchen-essentials', 'sort_order' => 42],
                ],
            ],
            [
                'name' => 'Beauty & Health',
                'slug' => 'beauty-health',
                'description' => 'Self-care products, skincare, and wellness bundles.',
                'sort_order' => 50,
                'children' => [
                    ['name' => 'Skincare', 'slug' => 'skincare', 'sort_order' => 51],
                    ['name' => 'Wellness', 'slug' => 'wellness', 'sort_order' => 52],
                ],
            ],
            [
                'name' => 'Sports & Outdoors',
                'slug' => 'sports-outdoors',
                'description' => 'Fitness, outdoor gear, and active lifestyle products.',
                'sort_order' => 60,
                'children' => [
                    ['name' => 'Fitness Gear', 'slug' => 'fitness-gear', 'sort_order' => 61],
                    ['name' => 'Cycling', 'slug' => 'cycling', 'sort_order' => 62],
                ],
            ],
        ];

        $count = 0;

        foreach ($categories as $categoryData) {
            $parent = Category::updateOrCreate(
                ['slug' => $categoryData['slug']],
                [
                    'name' => $categoryData['name'],
                    'description' => $categoryData['description'],
                    'parent_id' => null,
                    'sort_order' => $categoryData['sort_order'],
                    'is_active' => true,
                ]
            );

            $count++;

            foreach ($categoryData['children'] as $childData) {
                Category::updateOrCreate(
                    ['slug' => $childData['slug']],
                    [
                        'name' => $childData['name'],
                        'description' => "{$childData['name']} offers and curated product selections.",
                        'parent_id' => $parent->id,
                        'sort_order' => $childData['sort_order'],
                        'is_active' => true,
                    ]
                );

                $count++;
            }
        }

        $this->command->info("{$count} product categories seeded");
    }
}
