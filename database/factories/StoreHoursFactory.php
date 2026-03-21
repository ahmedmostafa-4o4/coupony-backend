<?php

namespace Database\Factories;

use App\Domain\Store\Models\Store;
use App\Domain\Store\Models\StoreHours;
use Illuminate\Database\Eloquent\Factories\Factory;

class StoreHoursFactory extends Factory
{
    protected $model = StoreHours::class;

    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'day_of_week' => fake()->numberBetween(0, 6),
            'open_time' => '09:00:00',
            'close_time' => '17:00:00',
            'is_closed' => false,
        ];
    }

    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_closed' => true,
        ]);
    }

    public function weekend(): static
    {
        return $this->state(fn (array $attributes) => [
            'day_of_week' => fake()->randomElement([0, 6]),
            'is_closed' => true,
        ]);
    }
}
