<?php

namespace Database\Seeders;

use App\Domain\Product\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class ProductCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name_en' => 'Electronics',
                'name_ar' => 'إلكترونيات',
                'slug' => 'electronics',
                'description' => 'Devices, accessories, and smart gadgets for daily use.',
                'icon_url' => config('app.url') . '/storage/categories/electronics.svg',
                'sort_order' => 10,
                'children' => [
                    ['name_en' => 'Smartphones', 'name_ar' => 'هواتف ذكية', 'slug' => 'smartphones', 'sort_order' => 11],
                    ['name_en' => 'Laptops', 'name_ar' => 'أجهزة لابتوب', 'slug' => 'laptops', 'sort_order' => 12],
                    ['name_en' => 'Audio', 'name_ar' => 'صوتيات', 'slug' => 'audio', 'sort_order' => 13],
                ],
            ],
            [
                'name_en' => 'Fashion',
                'name_ar' => 'أزياء',
                'slug' => 'fashion',
                'description' => 'Clothing, footwear, and accessories for every season.',
                'icon_url' => config('app.url') . '/storage/categories/fashion.svg',
                'sort_order' => 20,
                'children' => [
                    ['name_en' => 'Men Fashion', 'name_ar' => 'أزياء رجالية', 'slug' => 'men-fashion', 'sort_order' => 21],
                    ['name_en' => 'Women Fashion', 'name_ar' => 'أزياء نسائية', 'slug' => 'women-fashion', 'sort_order' => 22],
                    ['name_en' => 'Footwear', 'name_ar' => 'أحذية', 'slug' => 'footwear', 'sort_order' => 23],
                ],
            ],
            [
                'name_en' => 'Food & Beverages',
                'name_ar' => 'أطعمة ومشروبات',
                'slug' => 'food-beverages',
                'description' => 'Meal deals, grocery bundles, and cafe offers.',
                'icon_url' => config('app.url') . '/storage/categories/food-beverages.svg',
                'sort_order' => 30,
                'children' => [
                    ['name_en' => 'Restaurant Deals', 'name_ar' => 'عروض المطاعم', 'slug' => 'restaurant-deals', 'sort_order' => 31],
                    ['name_en' => 'Grocery Bundles', 'name_ar' => 'باقات البقالة', 'slug' => 'grocery-bundles', 'sort_order' => 32],
                    ['name_en' => 'Coffee & Desserts', 'name_ar' => 'قهوة وحلويات', 'slug' => 'coffee-desserts', 'sort_order' => 33],
                ],
            ],
            [
                'name_en' => 'Home & Garden',
                'name_ar' => 'المنزل والحديقة',
                'slug' => 'home-garden',
                'description' => 'Home essentials, decor, and practical upgrades.',
                'icon_url' => config('app.url') . '/storage/categories/home-garden.svg',
                'sort_order' => 40,
                'children' => [
                    ['name_en' => 'Home Decor', 'name_ar' => 'ديكور المنزل', 'slug' => 'home-decor', 'sort_order' => 41],
                    ['name_en' => 'Kitchen Essentials', 'name_ar' => 'أساسيات المطبخ', 'slug' => 'kitchen-essentials', 'sort_order' => 42],
                ],
            ],
            [
                'name_en' => 'Beauty & Health',
                'name_ar' => 'الجمال والصحة',
                'slug' => 'beauty-health',
                'description' => 'Self-care products, skincare, and wellness bundles.',
                'icon_url' => config('app.url') . '/storage/categories/beauty-health.svg',
                'sort_order' => 50,
                'children' => [
                    ['name_en' => 'Skincare', 'name_ar' => 'العناية بالبشرة', 'slug' => 'skincare', 'sort_order' => 51],
                    ['name_en' => 'Wellness', 'name_ar' => 'العناية الصحية', 'slug' => 'wellness', 'sort_order' => 52],
                ],
            ],
            [
                'name_en' => 'Sports & Outdoors',
                'name_ar' => 'الرياضة والأنشطة الخارجية',
                'slug' => 'sports-outdoors',
                'description' => 'Fitness, outdoor gear, and active lifestyle products.',
                'icon_url' => config('app.url') . '/storage/categories/sports-outdoors.svg',
                'sort_order' => 60,
                'children' => [
                    ['name_en' => 'Fitness Gear', 'name_ar' => 'معدات اللياقة', 'slug' => 'fitness-gear', 'sort_order' => 61],
                    ['name_en' => 'Cycling', 'name_ar' => 'الدراجات', 'slug' => 'cycling', 'sort_order' => 62],
                ],
            ],
        ];

        $count = 0;

        foreach ($categories as $categoryData) {
            $parentPayload = [
                'name' => $categoryData['name_en'],
                'name_ar' => $categoryData['name_ar'],
                'name_en' => $categoryData['name_en'],
                'description' => $categoryData['description'],
                'parent_id' => null,
                'sort_order' => $categoryData['sort_order'],
                'is_active' => true,
            ];

            if (Schema::hasColumn('categories', 'icon_url')) {
                $parentPayload['icon_url'] = $categoryData['icon_url'];
            }

            $parent = Category::updateOrCreate(
                ['slug' => $categoryData['slug']],
                $parentPayload
            );

            $count++;

            foreach ($categoryData['children'] as $childData) {
                $childPayload = [
                    'name' => $childData['name_en'],
                    'name_ar' => $childData['name_ar'],
                    'name_en' => $childData['name_en'],
                    'description' => "{$childData['name_en']} offers and curated product selections.",
                    'parent_id' => $parent->id,
                    'sort_order' => $childData['sort_order'],
                    'is_active' => true,
                ];

                if (Schema::hasColumn('categories', 'icon_url')) {
                    $childPayload['icon_url'] = config('app.url') . "/storage/categories/{$childData['slug']}.svg";
                }

                Category::updateOrCreate(
                    ['slug' => $childData['slug']],
                    $childPayload
                );

                $count++;
            }
        }

        $this->command->info("{$count} product categories seeded");
    }
}
