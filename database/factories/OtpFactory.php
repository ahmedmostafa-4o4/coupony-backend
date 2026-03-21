<?php

namespace Database\Factories;

use App\Domain\User\Models\Otp;
use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class OtpFactory extends Factory
{
    protected $model = Otp::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'phone_or_email' => fake()->email(),
            'otp_hash' => hash('sha256', '123456'),
            'purpose' => fake()->randomElement(['verify_email', 'verify_phone', 'login', 'reset_password']),
            'channel' => fake()->randomElement(['email', 'sms', 'whatsapp']),
            'status' => 'pending',
            'attempts' => 0,
            'max_attempts' => 3,
            'expires_at' => now()->addMinutes(10),
        ];
    }

    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'verified',
            'attempts' => 1,
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'expired',
            'expires_at' => now()->subMinutes(10),
        ]);
    }

    public function blocked(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'blocked',
            'attempts' => 3,
        ]);
    }
}
