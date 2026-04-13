<?php

namespace Database\Factories;

use App\Domain\Product\Enums\ProductApprovalStatus;
use App\Domain\Product\Enums\ProductOfferType;
use App\Domain\Product\Enums\ProductStatus;
use App\Domain\Product\Models\Product;
use App\Domain\Store\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function configure(): static
    {
        return $this->afterCreating(function (Product $product) {
            if ($product->offer()->exists()) {
                return;
            }

            $product->offer()->create([
                'type' => ProductOfferType::FIXED,
                'status' => 'active',
                'label' => 'Default fixed offer',
                'claim_expiration_minutes' => 1440,
                'fixed_amount' => 10,
            ]);
        });
    }

    public function definition(): array
    {
        $title = fake()->unique()->words(3, true);

        return [
            'store_id' => Store::factory(),
            'title' => ucfirst($title),
            'slug' => Str::slug($title),
            'short_description' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'base_price' => fake()->randomFloat(2, 10, 500),
            'compare_at_price' => fake()->randomFloat(2, 500, 700),
            'currency' => 'EGP',
            'sku' => strtoupper(fake()->unique()->bothify('SKU-####')),
            'status' => ProductStatus::INACTIVE,
            'approval_status' => ProductApprovalStatus::PENDING,
            'published_revision_no' => 0,
            'approved_at' => null,
            'approved_by' => null,
            'rejected_at' => null,
            'rejected_by' => null,
            'rejection_reason' => null,
            'admin_notes' => null,
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

    public function approved(): static
    {
        return $this->state(fn(array $attributes) => [
            'approval_status' => ProductApprovalStatus::APPROVED,
            'published_revision_no' => 1,
            'approved_at' => now(),
            'rejected_at' => null,
            'rejected_by' => null,
            'rejection_reason' => null,
        ]);
    }

    public function featured(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_featured' => true,
        ]);
    }
}
