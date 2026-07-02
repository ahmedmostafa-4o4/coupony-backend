<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's production reference data.
     */
    public function run(): void
    {
        $this->command?->info('Seeding production reference data...');

        $this->call([
            RoleAndPermissionSeeder::class,
            AdminUserSeeder::class,
            StoreCategorySeeder::class,
            ProductCategorySeeder::class,
            SocialSeeder::class,
            SubscriptionPlanSeeder::class,
        ]);

        $this->command?->info('Production reference data seeded successfully.');
    }
}
