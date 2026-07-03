<?php

namespace Database\Factories;

use App\Domain\User\Models\User;
use App\Domain\User\Models\UserDeviceToken;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserDeviceTokenFactory extends Factory
{
    protected $model = UserDeviceToken::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'token' => fake()->sha256(),
            'platform' => fake()->randomElement(['ios', 'android', 'web', 'unknown']),
            'device_id' => fake()->optional()->uuid(),
            'app_version' => fake()->optional()->semver(),
            'last_used_at' => now(),
            'revoked_at' => null,
        ];
    }

    public function revoked(): static
    {
        return $this->state(fn (array $attributes) => [
            'revoked_at' => now(),
        ]);
    }
}
