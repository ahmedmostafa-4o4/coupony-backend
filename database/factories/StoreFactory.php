<?php

namespace Database\Factories;

use App\Domain\Store\Enums\StoreStatus;
use App\Domain\Store\Models\Store;
use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class StoreFactory extends Factory
{
    protected $model = Store::class;

    public function definition(): array
    {
        return [
            'owner_user_id' => User::factory(),
            'name' => fake()->company(),
            'description' => fake()->paragraph(),
            'email' => fake()->companyEmail(),
            'phone' => fake()->phoneNumber(),
            'status' => StoreStatus::PENDING,
            'commission_rate' => 0.15,
            'rating_avg' => fake()->randomFloat(2, 3, 5),
            'rating_count' => fake()->numberBetween(0, 100),
            'total_sales' => fake()->randomFloat(2, 0, 10000),
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => StoreStatus::ACTIVE,
            'approved_at' => now(),
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => StoreStatus::PENDING,
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => StoreStatus::REJECTED,
            'rejected_at' => now(),
            'rejection_reason' => fake()->sentence(),
        ]);
    }

    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => StoreStatus::SUSPENDED,
        ]);
    }
}
