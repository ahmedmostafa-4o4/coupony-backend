<?php

namespace Database\Seeders;

use App\Domain\Store\Models\StoreCategory;
use Illuminate\Database\Seeder;

class StoreCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['id' => 1, 'name_ar' => 'إلكترونيات', 'name_en' => 'Electronics', 'slug' => 'electronics', 'icon_url' => 'store-categories/1/icon/8nQV7joemzgVHYfcqI1gYHhSMkbQCi7kg3VNkFoa.png', 'image_category' => null, 'sort_order' => 1, 'is_active' => true, 'created_at' => '2026-05-02 18:51:07', 'updated_at' => '2026-05-03 23:36:01'],
            ['id' => 2, 'name_ar' => 'أزياء وملابس', 'name_en' => 'Fashion & Clothing', 'slug' => 'fashion-clothing', 'icon_url' => 'store-categories/2/icon/4daK0JmYpwWAbR4VmGhQaB3alJFFRCGHPDnk2WhZ.png', 'image_category' => null, 'sort_order' => 2, 'is_active' => true, 'created_at' => '2026-05-02 18:51:07', 'updated_at' => '2026-05-03 21:44:33'],
            ['id' => 3, 'name_ar' => 'أطعمة ومشروبات', 'name_en' => 'Food & Beverages', 'slug' => 'food-beverages', 'icon_url' => 'store-categories/3/icon/aUDLwYEl4REXDS53Ptq1zXjvk0049MYPHQUhKer3.png', 'image_category' => null, 'sort_order' => 3, 'is_active' => true, 'created_at' => '2026-05-02 18:51:07', 'updated_at' => '2026-05-03 22:02:34'],
            ['id' => 4, 'name_ar' => 'المنزل والحديقة', 'name_en' => 'Home & Garden', 'slug' => 'home-garden', 'icon_url' => 'store-categories/4/icon/PpH2MRDM59AUtLMzKulEPucYBNddnPoXLEcv75mJ.png', 'image_category' => null, 'sort_order' => 4, 'is_active' => true, 'created_at' => '2026-05-02 18:51:07', 'updated_at' => '2026-05-03 23:28:43'],
            ['id' => 5, 'name_ar' => 'الجمال والصحة', 'name_en' => 'Beauty & Health', 'slug' => 'beauty-health', 'icon_url' => 'store-categories/5/icon/83U49JLthpP4yDE18maEOgulRyb5sYmbgYoXSou7.png', 'image_category' => null, 'sort_order' => 5, 'is_active' => true, 'created_at' => '2026-05-02 18:51:07', 'updated_at' => '2026-05-03 20:32:24'],
            ['id' => 6, 'name_ar' => 'الرياضة والأنشطة الخارجية', 'name_en' => 'Sports & Outdoors', 'slug' => 'sports-outdoors', 'icon_url' => 'store-categories/6/icon/Dp3sKimRw3Cw04lZypBNX7KCZcvaG06ORSBdMgGi.png', 'image_category' => null, 'sort_order' => 6, 'is_active' => true, 'created_at' => '2026-05-02 18:51:07', 'updated_at' => '2026-05-03 20:35:12'],
            ['id' => 7, 'name_ar' => 'كتب ووسائط', 'name_en' => 'Books & Media', 'slug' => 'books-media', 'icon_url' => 'store-categories/7/icon/DmHHrqNgbx22DkBojwXtZJRbNN95leccNvUjHlwP.png', 'image_category' => null, 'sort_order' => 7, 'is_active' => true, 'created_at' => '2026-05-02 18:51:07', 'updated_at' => '2026-05-03 20:48:15'],
            ['id' => 8, 'name_ar' => 'ألعاب وترفيه', 'name_en' => 'Toys & Games', 'slug' => 'toys-games', 'icon_url' => 'store-categories/8/icon/0guEBPATMilEjr0yCsImxoVIHHesCHwnHI9rwKy9.png', 'image_category' => null, 'sort_order' => 8, 'is_active' => true, 'created_at' => '2026-05-02 18:51:07', 'updated_at' => '2026-05-03 23:29:40'],
            ['id' => 9, 'name_ar' => 'سيارات', 'name_en' => 'Automotive', 'slug' => 'automotive', 'icon_url' => 'store-categories/9/icon/3MfR9rRkQSW1QqKYUwQI2xybNQMO5HAi4BNILSlj.png', 'image_category' => null, 'sort_order' => 9, 'is_active' => true, 'created_at' => '2026-05-02 18:51:07', 'updated_at' => '2026-05-03 20:50:22'],
            ['id' => 10, 'name_ar' => 'مجوهرات وإكسسوارات', 'name_en' => 'Jewelry & Accessories', 'slug' => 'jewelry-accessories', 'icon_url' => 'store-categories/10/icon/GZCgGSQI2QqciUY0Hh8ATS5TATjTWBy3y4N5XLKn.png', 'image_category' => null, 'sort_order' => 10, 'is_active' => true, 'created_at' => '2026-05-02 18:51:07', 'updated_at' => '2026-05-03 20:50:37'],
            ['id' => 11, 'name_ar' => 'مستلزمات الحيوانات الأليفة', 'name_en' => 'Pet Supplies', 'slug' => 'pet-supplies', 'icon_url' => 'store-categories/11/icon/kIA7zAIwAaOVUYTamwmk8dnw9YHrog1t2hQTlSE7.png', 'image_category' => null, 'sort_order' => 11, 'is_active' => true, 'created_at' => '2026-05-02 18:51:07', 'updated_at' => '2026-05-03 20:51:01'],
            ['id' => 12, 'name_ar' => 'مستلزمات مكتبية', 'name_en' => 'Office Supplies', 'slug' => 'office-supplies', 'icon_url' => 'store-categories/12/icon/xFxCCmbeKXrJiPgQfKIAYtbseQg3xfWYYzrzZzaK.png', 'image_category' => null, 'sort_order' => 12, 'is_active' => true, 'created_at' => '2026-05-02 18:51:07', 'updated_at' => '2026-05-03 20:52:50'],
            ['id' => 13, 'name_ar' => 'الأطفال والرضع', 'name_en' => 'Baby & Kids', 'slug' => 'baby-kids', 'icon_url' => 'store-categories/13/icon/EJIPoygEvxGpv7ALjWxxoeAUtyF8SoEGbKOi3yrO.png', 'image_category' => null, 'sort_order' => 13, 'is_active' => true, 'created_at' => '2026-05-02 18:51:07', 'updated_at' => '2026-05-03 23:29:55'],
            ['id' => 14, 'name_ar' => 'أثاث', 'name_en' => 'Furniture', 'slug' => 'furniture', 'icon_url' => 'store-categories/14/icon/R4exEeL3J1jVrq4njAI7sgwxEw1ZOw4bA9IyZ59o.png', 'image_category' => null, 'sort_order' => 14, 'is_active' => true, 'created_at' => '2026-05-02 18:51:07', 'updated_at' => '2026-05-03 20:56:19'],
            ['id' => 15, 'name_ar' => 'بقالة', 'name_en' => 'Grocery', 'slug' => 'grocery', 'icon_url' => 'store-categories/15/icon/Dx5WgRl35c699H9lVuglU3a9jyT4Awj7sn25x7X0.png', 'image_category' => null, 'sort_order' => 15, 'is_active' => true, 'created_at' => '2026-05-02 18:51:07', 'updated_at' => '2026-05-03 20:56:34'],
        ];

        foreach ($categories as $category) {
            StoreCategory::query()->updateOrCreate(
                ['id' => $category['id']],
                $category
            );
        }

        $this->command->info(count($categories) . ' store categories seeded from export');
    }
}
