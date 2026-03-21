<?php

namespace Database\Factories;

use App\Domain\User\Models\Profile;
use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\User\Models\Profile>
 */
class ProfileFactory extends Factory
{
    protected $model = Profile::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'avatar_url' => null,
            'bio' => fake()->optional()->sentence(),
            'date_of_birth' => fake()->optional()->date(),
            'gender' => fake()->optional()->randomElement(['male', 'female', 'other']),
        ];
    }
}
