<?php

namespace Database\Seeders;

use App\Domain\Store\Models\StoreCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class StoreCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name_en' => 'Electronics', 'name_ar' => 'إلكترونيات', 'slug' => 'electronics', 'image_category' => 'electronics'],
            ['name_en' => 'Fashion & Clothing', 'name_ar' => 'أزياء وملابس', 'slug' => 'fashion-clothing', 'image_category' => 'fashion'],
            ['name_en' => 'Food & Beverages', 'name_ar' => 'أطعمة ومشروبات', 'slug' => 'food-beverages', 'image_category' => 'food'],
            ['name_en' => 'Home & Garden', 'name_ar' => 'المنزل والحديقة', 'slug' => 'home-garden', 'image_category' => 'home'],
            ['name_en' => 'Beauty & Health', 'name_ar' => 'الجمال والصحة', 'slug' => 'beauty-health', 'image_category' => 'beauty'],
            ['name_en' => 'Sports & Outdoors', 'name_ar' => 'الرياضة والأنشطة الخارجية', 'slug' => 'sports-outdoors', 'image_category' => 'sports'],
            ['name_en' => 'Books & Media', 'name_ar' => 'كتب ووسائط', 'slug' => 'books-media', 'image_category' => 'books'],
            ['name_en' => 'Toys & Games', 'name_ar' => 'ألعاب وترفيه', 'slug' => 'toys-games', 'image_category' => 'toys'],
            ['name_en' => 'Automotive', 'name_ar' => 'سيارات', 'slug' => 'automotive', 'image_category' => 'automotive'],
            ['name_en' => 'Jewelry & Accessories', 'name_ar' => 'مجوهرات وإكسسوارات', 'slug' => 'jewelry-accessories', 'image_category' => 'jewelry'],
            ['name_en' => 'Pet Supplies', 'name_ar' => 'مستلزمات الحيوانات الأليفة', 'slug' => 'pet-supplies', 'image_category' => 'pets'],
            ['name_en' => 'Office Supplies', 'name_ar' => 'مستلزمات مكتبية', 'slug' => 'office-supplies', 'image_category' => 'office'],
            ['name_en' => 'Baby & Kids', 'name_ar' => 'الأطفال والرضع', 'slug' => 'baby-kids', 'image_category' => 'kids'],
            ['name_en' => 'Furniture', 'name_ar' => 'أثاث', 'slug' => 'furniture', 'image_category' => 'furniture'],
            ['name_en' => 'Grocery', 'name_ar' => 'بقالة', 'slug' => 'grocery', 'image_category' => 'grocery'],
        ];

        foreach ($categories as $index => $category) {
            $payload = array_merge($category, [
                'sort_order' => ($index + 1) * 10,
                'is_active' => true,
            ]);

            if (Schema::hasColumn('store_categories', 'icon_url')) {
                $payload['icon_url'] = config('app.url') . "/storage/categories/{$category['slug']}.svg";
            }

            StoreCategory::updateOrCreate(
                ['slug' => $category['slug']],
                $payload
            );
        }

        $this->command->info('15 store categories created');
    }
}
