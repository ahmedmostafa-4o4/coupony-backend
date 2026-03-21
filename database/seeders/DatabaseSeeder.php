<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('🌱 Starting database seeding...');
        $this->command->newLine();

        // Seed in order of dependencies
        $this->call([
            RoleAndPermissionSeeder::class,
            UserSeeder::class,
            StoreCategorySeeder::class,
            StoreSeeder::class,
            NotificationSeeder::class,
        ]);

        $this->command->newLine();
        $this->command->info('✅ Database seeding completed successfully!');
        $this->command->newLine();
        
        $this->command->table(
            ['Credential', 'Value'],
            [
                ['Admin Email', 'admin@coupony.com'],
                ['Admin Password', 'password'],
                ['Seller Emails', 'seller1-5@example.com'],
                ['Seller Password', 'password'],
            ]
        );
    }
}
