<?php

namespace Database\Factories;

use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\User\Models\User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'email' => fake()->unique()->safeEmail(),
            'password_hash' => static::$password ??= Hash::make('password'),
            'phone_number' => fake()->phoneNumber(),
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
            'status' => 'active',
            'last_login_at' => null,
            'login_count' => 0,
            'remember_token' => Str::random(10),
            'two_factor_enabled' => false,
            'last_ip' => fake()->ipv4(),
            'provider' => null,
            'provider_id' => null,
            'language' => 'ar',
            'timezone' => 'Africa/Cairo',
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn(array $attributes) => [
        'email_verified_at' => null,
        'phone_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the user is suspended.
     */
    public function suspended(): static
    {
        return $this->state(fn(array $attributes) => [
        'status' => 'suspended',
        ]);
    }

    /**
     * Indicate that the user is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn(array $attributes) => [
        'status' => 'inactive',
        ]);
    }

    /**
     * Configure the model factory.
     */
    public function configure()
    {
        return $this->afterCreating(function (User $user) {
            // Create a profile for the user
            $user->profile()->create([
                'first_name' => fake()->firstName(),
                'last_name' => fake()->lastName(),
            ]);
        });
    }
}
