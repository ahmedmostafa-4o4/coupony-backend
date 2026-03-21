<?php

namespace Database\Seeders;

use App\Domain\Store\Models\StoreCategory;
use Illuminate\Database\Seeder;

class StoreCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Electronics', 'slug' => 'electronics', 'is_active' => true],
            ['name' => 'Fashion & Clothing', 'slug' => 'fashion-clothing', 'is_active' => true],
            ['name' => 'Food & Beverages', 'slug' => 'food-beverages', 'is_active' => true],
            ['name' => 'Home & Garden', 'slug' => 'home-garden', 'is_active' => true],
            ['name' => 'Beauty & Health', 'slug' => 'beauty-health', 'is_active' => true],
            ['name' => 'Sports & Outdoors', 'slug' => 'sports-outdoors', 'is_active' => true],
            ['name' => 'Books & Media', 'slug' => 'books-media', 'is_active' => true],
            ['name' => 'Toys & Games', 'slug' => 'toys-games', 'is_active' => true],
            ['name' => 'Automotive', 'slug' => 'automotive', 'is_active' => true],
            ['name' => 'Jewelry & Accessories', 'slug' => 'jewelry-accessories', 'is_active' => true],
            ['name' => 'Pet Supplies', 'slug' => 'pet-supplies', 'is_active' => true],
            ['name' => 'Office Supplies', 'slug' => 'office-supplies', 'is_active' => true],
            ['name' => 'Baby & Kids', 'slug' => 'baby-kids', 'is_active' => true],
            ['name' => 'Furniture', 'slug' => 'furniture', 'is_active' => true],
            ['name' => 'Grocery', 'slug' => 'grocery', 'is_active' => true],
        ];

        foreach ($categories as $category) {
            StoreCategory::create($category);
        }

        $this->command->info('15 store categories created');
    }
}
