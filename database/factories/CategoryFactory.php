<?php

namespace Database\Factories;

use App\Domain\Product\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function configure(): static
    {
        return $this->afterMaking(function (Category $category) {
            if (filled($category->name) && $category->name !== $category->name_en) {
                $category->name_en = $category->name;
                $category->name_ar = $category->name;
                $category->slug = Str::slug($category->name);
            }
        });
    }

    public function definition(): array
    {
        $nameEn = ucfirst(fake()->unique()->words(2, true));
        $nameAr = 'تصنيف ' . fake()->unique()->numberBetween(1000, 9999);

        return [
            'name' => $nameEn,
            'name_ar' => $nameAr,
            'name_en' => $nameEn,
            'slug' => Str::slug($nameEn),
            'description' => fake()->sentence(),
            'parent_id' => null,
            'sort_order' => fake()->numberBetween(0, 100),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_active' => false,
        ]);
    }
}
