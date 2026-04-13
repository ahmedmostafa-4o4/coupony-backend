<?php

namespace Database\Factories;

use App\Domain\Product\Enums\InventoryMode;
use App\Domain\Product\Models\Product;
use App\Domain\Product\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductVariantFactory extends Factory
{
    protected $model = ProductVariant::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'title' => fake()->words(2, true),
            'option_summary' => fake()->sentence(3),
            'sku' => strtoupper(fake()->unique()->bothify('VAR-####')),
            'barcode' => fake()->numerify('#########'),
            'price' => fake()->randomFloat(2, 10, 500),
            'compare_at_price' => fake()->randomFloat(2, 500, 700),
            'currency' => 'EGP',
            'sort_order' => 0,
            'is_default' => false,
            'is_active' => true,
            'inventory_mode' => InventoryMode::UNLIMITED,
            'stock_qty' => null,
            'low_stock_threshold' => null,
            'allow_backorder' => false,
            'sale_count' => 0,
            'redemption_count' => 0,
        ];
    }
}
