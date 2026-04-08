<?php

namespace Database\Factories;

use App\Domain\Product\Enums\ProductStatus;
use App\Domain\Product\Enums\ProductType;
use App\Domain\Product\Models\Product;
use App\Domain\Store\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $title = fake()->unique()->words(3, true);

        return [
            'store_id' => Store::factory(),
            'title' => ucfirst($title),
            'slug' => Str::slug($title),
            'short_description' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'product_type' => ProductType::STANDARD,
            'base_price' => fake()->randomFloat(2, 10, 500),
            'compare_at_price' => fake()->randomFloat(2, 500, 700),
            'currency' => 'EGP',
            'sku' => strtoupper(fake()->unique()->bothify('SKU-####')),
            'status' => ProductStatus::DRAFT,
            'is_featured' => false,
            'sale_count' => 0,
            'redemption_count' => 0,
        ];
    }

    public function active(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => ProductStatus::ACTIVE,
        ]);
    }

    public function featured(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_featured' => true,
        ]);
    }
}
