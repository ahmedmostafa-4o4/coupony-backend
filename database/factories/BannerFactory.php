<?php

namespace Database\Factories;

use App\Domain\Banner\Enums\BannerStatus;
use App\Domain\Banner\Models\Banner;
use App\Domain\Store\Models\Store;
use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class BannerFactory extends Factory
{
    protected $model = Banner::class;

    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'requested_by' => User::factory(),
            'image_url' => 'banners/example.jpg',
            'discount_label' => fake()->randomElement(['20% OFF', 'Buy 1 Get 1', 'EGP 50 OFF']),
            'date_range' => fake()->date('M d').' - '.fake()->date('M d'),
            'cta_label' => 'Claim now',
            'terms_of_use' => fake()->sentence(),
            'end_time' => now()->addDays(7),
            'priority' => fake()->numberBetween(1, 100),
            'is_active' => false,
            'status' => BannerStatus::PENDING,
            'approved_at' => null,
            'approved_by' => null,
            'rejection_reason' => null,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => BannerStatus::APPROVED,
            'is_active' => true,
            'approved_at' => now(),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'end_time' => now()->subMinute(),
        ]);
    }
}
