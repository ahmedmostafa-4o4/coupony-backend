<?php

namespace Database\Factories;

use App\Domain\Subscription\Models\SubscriptionPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubscriptionPlanFactory extends Factory
{
    protected $model = SubscriptionPlan::class;

    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Basic', 'Premium', 'Enterprise']),
            'slug' => fake()->unique()->slug(2),
            'description' => fake()->sentence(),
            'price_monthly' => fake()->randomFloat(2, 49, 499),
            'price_yearly' => fake()->randomFloat(2, 490, 4990),
            'currency' => 'EGP',
            'max_products' => fake()->numberBetween(10, 1000),
            'max_employees' => fake()->numberBetween(1, 50),
            'max_branches' => fake()->numberBetween(1, 20),
            'max_ai_messages_per_day' => 15,
            'features' => ['ai_assistant' => true, 'analytics' => true],
            'grace_period_days' => 7,
            'degraded_period_days' => 14,
            'is_active' => true,
            'sort_order' => fake()->numberBetween(1, 10),
        ];
    }
}
