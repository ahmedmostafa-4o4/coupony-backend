<?php

namespace Database\Factories;

use App\Domain\Notification\Models\Notification;
use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class NotificationFactory extends Factory
{
    protected $model = Notification::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => fake()->randomElement(['info', 'success', 'warning', 'error', 'store_approved', 'store_rejected', 'otp_email']),
            'title' => fake()->sentence(),
            'message' => fake()->paragraph(),
            'channel' => fake()->randomElement(['email', 'sms', 'push', 'in_app']),
            'status' => fake()->randomElement(['pending', 'sent', 'failed', 'read']),
            'sent_at' => fake()->optional()->dateTimeBetween('-1 week', 'now'),
            'read_at' => fake()->optional()->dateTimeBetween('-1 week', 'now'),
            'data' => null,
            'reference_type' => null,
            'reference_id' => null,
        ];
    }

    public function sent(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    public function read(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'read',
            'sent_at' => now()->subHours(2),
            'read_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'sent_at' => now(),
        ]);
    }
}
