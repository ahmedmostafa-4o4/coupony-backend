<?php

namespace Database\Factories;

use App\Domain\User\Models\User;
use App\Domain\User\Models\UserPreference;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserPreferenceFactory extends Factory
{
    protected $model = UserPreference::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'email_marketing' => fake()->boolean(70),
            'email_order_updates' => fake()->boolean(90),
            'sms_notifications' => fake()->boolean(40),
            'push_notifications' => fake()->boolean(80),
            'preferred_currency' => fake()->randomElement(['USD', 'EUR', 'EGP', 'SAR']),
            'preferred_language' => fake()->randomElement(['en', 'ar']),
            'preferred_payment_method' => fake()->optional()->randomElement(['credit_card', 'debit_card', 'paypal', 'cash']),
            'enable_personalized_recommendations' => fake()->boolean(85),
            'browsing_history_tracking' => fake()->boolean(75),
            'show_profile_publicly' => fake()->boolean(30),
            'allow_data_sharing_for_analytics' => fake()->boolean(60),
        ];
    }

    public function allNotificationsEnabled(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_marketing' => true,
            'email_order_updates' => true,
            'sms_notifications' => true,
            'push_notifications' => true,
        ]);
    }

    public function allNotificationsDisabled(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_marketing' => false,
            'email_order_updates' => false,
            'sms_notifications' => false,
            'push_notifications' => false,
        ]);
    }
}
