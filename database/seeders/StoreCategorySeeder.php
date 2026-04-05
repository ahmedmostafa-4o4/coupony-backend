<?php

namespace Database\Seeders;

use App\Domain\Store\Models\StoreCategory;
use Illuminate\Database\Seeder;

class StoreCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name_en' => 'Electronics', 'name_ar' => 'إلكترونيات', 'slug' => 'electronics', 'is_active' => true],
            ['name_en' => 'Fashion & Clothing', 'name_ar' => 'أزياء وملابس', 'slug' => 'fashion-clothing', 'is_active' => true],
            ['name_en' => 'Food & Beverages', 'name_ar' => 'أطعمة ومشروبات', 'slug' => 'food-beverages', 'is_active' => true],
            ['name_en' => 'Home & Garden', 'name_ar' => 'المنزل والحديقة', 'slug' => 'home-garden', 'is_active' => true],
            ['name_en' => 'Beauty & Health', 'name_ar' => 'الجمال والصحة', 'slug' => 'beauty-health', 'is_active' => true],
            ['name_en' => 'Sports & Outdoors', 'name_ar' => 'الرياضة والأنشطة الخارجية', 'slug' => 'sports-outdoors', 'is_active' => true],
            ['name_en' => 'Books & Media', 'name_ar' => 'كتب ووسائط', 'slug' => 'books-media', 'is_active' => true],
            ['name_en' => 'Toys & Games', 'name_ar' => 'ألعاب وترفيه', 'slug' => 'toys-games', 'is_active' => true],
            ['name_en' => 'Automotive', 'name_ar' => 'سيارات', 'slug' => 'automotive', 'is_active' => true],
            ['name_en' => 'Jewelry & Accessories', 'name_ar' => 'مجوهرات وإكسسوارات', 'slug' => 'jewelry-accessories', 'is_active' => true],
            ['name_en' => 'Pet Supplies', 'name_ar' => 'مستلزمات الحيوانات الأليفة', 'slug' => 'pet-supplies', 'is_active' => true],
            ['name_en' => 'Office Supplies', 'name_ar' => 'مستلزمات مكتبية', 'slug' => 'office-supplies', 'is_active' => true],
            ['name_en' => 'Baby & Kids', 'name_ar' => 'الأطفال والرضع', 'slug' => 'baby-kids', 'is_active' => true],
            ['name_en' => 'Furniture', 'name_ar' => 'أثاث', 'slug' => 'furniture', 'is_active' => true],
            ['name_en' => 'Grocery', 'name_ar' => 'بقالة', 'slug' => 'grocery', 'is_active' => true],
        ];

        foreach ($categories as $category) {
            StoreCategory::create($category);
        }

        $this->command->info('15 store categories created');
    }
}
