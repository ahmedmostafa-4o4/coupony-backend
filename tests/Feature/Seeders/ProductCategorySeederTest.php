<?php

namespace Tests\Feature\Seeders;

use App\Domain\Product\Models\Category;
use Database\Seeders\ProductCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductCategorySeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_travel_categories_from_production_export(): void
    {
        $this->seed(ProductCategorySeeder::class);

        $this->assertDatabaseHas('categories', [
            'id' => 22,
            'name_en' => 'Flights and Travel',
            'slug' => 'flights-and-travel',
            'parent_id' => null,
        ]);
        $this->assertDatabaseHas('categories', [
            'id' => 23,
            'name_en' => 'Inside Egypt',
            'slug' => 'inside-egypt',
            'parent_id' => 22,
        ]);
        $this->assertDatabaseHas('categories', [
            'id' => 24,
            'name_en' => 'Outside Egypt',
            'slug' => 'outside-egypt',
            'parent_id' => 22,
        ]);
        $this->assertSame(23, Category::query()->count());
    }
}
