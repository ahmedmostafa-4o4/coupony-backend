<?php

namespace Database\Seeders;

use App\Domain\Store\Enums\StoreStatus;
use App\Domain\Store\Models\Store;
use App\Domain\Store\Models\StoreCategory;
use App\Domain\Store\Models\StoreHours;
use App\Domain\Store\Models\StoreVerification;
use App\Domain\User\Models\Address;
use App\Domain\User\Models\User;
use Illuminate\Database\Seeder;

class StoreSeeder extends Seeder
{
    public function run(): void
    {
        $sellers = User::role('seller')->get();
        $sellersPending = User::role('seller_pending')->get();
        $categories = StoreCategory::all();

        if ($sellers->isEmpty() || $categories->isEmpty()) {
            $this->command->warn('Please run UserSeeder and StoreCategorySeeder first!');
            return;
        }

        // Create Active Stores for approved sellers
        foreach ($sellers as $seller) {
            $store = Store::create([
                'owner_user_id' => $seller->id,
                'name' => fake()->company() . ' Store',
                'description' => fake()->paragraph(3),
                'email' => fake()->companyEmail(),
                'phone' => fake()->phoneNumber(),
                'status' => StoreStatus::ACTIVE,
                'commission_rate' => 0.15,
                'rating_avg' => fake()->randomFloat(2, 3.5, 5),
                'rating_count' => fake()->numberBetween(10, 500),
                'total_sales' => fake()->randomFloat(2, 1000, 50000),
                'approved_at' => now()->subDays(rand(1, 30)),
                'approved_by' => User::role('admin')->first()->id,
            ]);

            // Attach random categories
            $store->categories()->attach(
                $categories->random(rand(1, 3))->pluck('id')
            );

            // Create address
            $address = Address::create([
                'address_line1' => fake()->streetAddress(),
                'city' => fake()->city(),
                'state_province' => fake()->state(),
                'postal_code' => fake()->postcode(),
                'country_code' => 'EG',
                'latitude' => fake()->latitude(25, 31),
                'longitude' => fake()->longitude(25, 35),
            ]);

            $store->addresses()->attach($address->id, ['label' => 'branch']);

            // Create store hours
            $this->createStoreHours($store);

            // Create verifications
            $this->createVerifications($store, 'approved');
        }

        $this->command->info("{$sellers->count()} active stores created");

        // Create Pending Stores for pending sellers
        foreach ($sellersPending as $seller) {
            $store = Store::create([
                'owner_user_id' => $seller->id,
                'name' => fake()->company() . ' Store',
                'description' => fake()->paragraph(3),
                'email' => fake()->companyEmail(),
                'phone' => fake()->phoneNumber(),
                'status' => StoreStatus::PENDING,
                'commission_rate' => 0.15,
            ]);

            $store->categories()->attach(
                $categories->random(rand(1, 2))->pluck('id')
            );

            $address = Address::create([
                'address_line1' => fake()->streetAddress(),
                'city' => fake()->city(),
                'state_province' => fake()->state(),
                'postal_code' => fake()->postcode(),
                'country_code' => 'EG',
                'latitude' => fake()->latitude(25, 31),
                'longitude' => fake()->longitude(25, 35),
            ]);

            $store->addresses()->attach($address->id, ['label' => 'branch']);

            $this->createStoreHours($store);
            $this->createVerifications($store, 'pending');
        }

        $this->command->info("{$sellersPending->count()} pending stores created");

        // Create some rejected stores
        $rejectedSellers = User::role('customer')->take(2)->get();
        foreach ($rejectedSellers as $seller) {
            $store = Store::create([
                'owner_user_id' => $seller->id,
                'name' => fake()->company() . ' Store',
                'description' => fake()->paragraph(3),
                'email' => fake()->companyEmail(),
                'phone' => fake()->phoneNumber(),
                'status' => StoreStatus::REJECTED,
                'commission_rate' => 0.15,
                'rejected_at' => now()->subDays(rand(1, 10)),
                'rejected_by' => User::role('admin')->first()->id,
                'rejection_reason' => 'Invalid or incomplete documentation provided.',
            ]);

            $store->categories()->attach($categories->random(1)->pluck('id'));

            $address = Address::create([
                'address_line1' => fake()->streetAddress(),
                'city' => fake()->city(),
                'state_province' => fake()->state(),
                'postal_code' => fake()->postcode(),
                'country_code' => 'EG',
                'latitude' => fake()->latitude(25, 31),
                'longitude' => fake()->longitude(25, 35),
            ]);

            $store->addresses()->attach($address->id, ['label' => 'branch']);

            $this->createStoreHours($store);
            $this->createVerifications($store, 'rejected');
        }

        $this->command->info('2 rejected stores created');
    }

    private function createStoreHours(Store $store): void
    {
        $hours = [
            ['day_of_week' => 1, 'open_time' => '09:00', 'close_time' => '18:00', 'is_closed' => false],
            ['day_of_week' => 2, 'open_time' => '09:00', 'close_time' => '18:00', 'is_closed' => false],
            ['day_of_week' => 3, 'open_time' => '09:00', 'close_time' => '18:00', 'is_closed' => false],
            ['day_of_week' => 4, 'open_time' => '09:00', 'close_time' => '18:00', 'is_closed' => false],
            ['day_of_week' => 5, 'open_time' => '09:00', 'close_time' => '18:00', 'is_closed' => false],
            ['day_of_week' => 6, 'open_time' => '10:00', 'close_time' => '16:00', 'is_closed' => false],
            ['day_of_week' => 0, 'open_time' => '00:00', 'close_time' => '00:00', 'is_closed' => true],
        ];

        foreach ($hours as $hour) {
            StoreHours::create(array_merge(['store_id' => $store->id], $hour));
        }
    }

    private function createVerifications(Store $store, string $status): void
    {
        $documents = ['commercial_register', 'tax_card', 'id_card_front', 'id_card_back'];
        
        foreach ($documents as $docType) {
            $verification = [
                'store_id' => $store->id,
                'document_type' => $docType,
                'document_path' => "documents/{$store->id}/{$docType}.pdf",
                'status' => $status,
            ];

            if ($status !== 'pending') {
                $verification['verified_by'] = User::role('admin')->first()->id;
                $verification['verified_at'] = now();
                
                if ($status === 'rejected') {
                    $verification['rejection_reason'] = 'Document is not clear or invalid';
                }
            }

            StoreVerification::create($verification);
        }
    }
}

