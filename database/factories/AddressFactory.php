<?php

namespace Database\Factories;

use App\Domain\User\Models\Address;
use Illuminate\Database\Eloquent\Factories\Factory;

class AddressFactory extends Factory
{
    protected $model = Address::class;

    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'company' => fake()->optional()->company(),
            'address_line1' => fake()->streetAddress(),
            'address_line2' => fake()->optional()->secondaryAddress(),
            'city' => fake()->city(),
            'state_province' => fake()->optional()->state(),
            'postal_code' => fake()->postcode(),
            'country_code' => fake()->countryCode(),
            'phone_number' => fake()->phoneNumber(),
            'latitude' => fake()->latitude(25, 31),
            'longitude' => fake()->longitude(25, 35),
            'delivery_instructions' => fake()->optional()->sentence(),
        ];
    }
}
