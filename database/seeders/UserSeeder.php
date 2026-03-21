<?php

namespace Database\Seeders;

use App\Domain\User\Models\Profile;
use App\Domain\User\Models\User;
use App\Domain\User\Models\UserPreference;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function __construct(
        private Hasher $hasher
    )
    {
        
    }
    public function run(): void
    {
        // Create Admin User
        $admin = User::create([
            'email' => 'admin@coupony.com',
            'phone_number' => '+201234567890',
            'password_hash' => $this->hasher->make('password'),
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
            'status' => 'active',
        ]);
        $admin->assignRole('admin');
        
        Profile::create([
            'user_id' => $admin->id,
            'first_name' => 'Admin',
            'last_name' => 'User',
            'date_of_birth' => '1990-01-01',
            'gender' => 'male',
        ]);

        UserPreference::create([
            'user_id' => $admin->id,
            'preferred_language' => 'en',
            'preferred_currency' => 'USD',
            'email_marketing' => true,
            'email_order_updates' => true,
            'sms_notifications' => true,
            'push_notifications' => true,
        ]);

        $this->command->info('Admin user created: admin@coupony.com / password');

        // Create Seller Users
        for ($i = 1; $i <= 5; $i++) {
            $seller = User::create([
                'email' => "seller{$i}@example.com",
                'phone_number' => "+20123456789{$i}",
                'password_hash' => Hash::make('password'),
                'email_verified_at' => now(),
                'phone_verified_at' => now(),
                'status' => 'active',
            ]);
            $seller->assignRole($i <= 3 ? 'seller' : 'seller_pending');
            
            Profile::create([
                'user_id' => $seller->id,
                'first_name' => "Seller",
                'last_name' => "User {$i}",
                'date_of_birth' => '1985-05-15',
                'gender' => fake()->randomElement(['male', 'female']),
            ]);

            UserPreference::create([
                'user_id' => $seller->id,
                'preferred_language' => 'en',
                'preferred_currency' => 'USD',
            ]);
        }

        $this->command->info('5 seller users created (seller1-5@example.com / password)');

        // Create Customer Users
        User::factory()
            ->count(20)
            ->create()
            ->each(function ($user) {
                $user->assignRole('customer');
            });

        $this->command->info('20 customer users created');
    }
}
