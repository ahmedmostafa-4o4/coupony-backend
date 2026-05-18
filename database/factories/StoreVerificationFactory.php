<?php

namespace Database\Factories;

use App\Domain\Store\Models\Store;
use App\Domain\Store\Models\StoreVerification;
use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class StoreVerificationFactory extends Factory
{
    protected $model = StoreVerification::class;

    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'document_type' => fake()->randomElement(['commercial_register', 'tax_card', 'id_card_front', 'id_card_back']),
            'document_path' => 'documents/'.fake()->uuid().'.pdf',
            'status' => 'pending',
            'verified_by' => null,
            'verified_at' => null,
            'rejection_reason' => null,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'verified_by' => User::factory(),
            'verified_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
            'verified_by' => User::factory(),
            'verified_at' => now(),
            'rejection_reason' => fake()->sentence(),
        ]);
    }
}
