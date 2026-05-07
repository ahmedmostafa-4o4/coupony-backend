<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('🌱 Starting fresh demo database seeding...');
        $this->command->newLine();

        $this->guardFreshDemoSeed();
        $this->cleanSeededTables();

        $this->call([
            RoleAndPermissionSeeder::class,
            StoreCategorySeeder::class,
            ProductCategorySeeder::class,
            SocialSeeder::class,
            LargeDemoSeeder::class,
        ]);

        $this->command->newLine();
        $this->command->info('✅ Fresh demo database seeding completed successfully!');
        $this->command->newLine();
        
        $this->command->table(
            ['Credential', 'Value'],
            [
                ['Admin Email', 'admin@coupony.com'],
                ['Admin Password', 'password'],
                ['Seller Emails', 'seller1-12@example.com'],
                ['Seller Password', 'password'],
                ['Customer Emails', 'customer1-80@example.com'],
                ['Customer Password', 'password'],
            ]
        );
    }

    private function guardFreshDemoSeed(): void
    {
        if (app()->environment(['local', 'testing']) || env('SEED_ALLOW_FRESH', false)) {
            return;
        }

        throw new RuntimeException(
            'Fresh demo seeding is destructive. Run in local/testing or set SEED_ALLOW_FRESH=true.'
        );
    }

    private function cleanSeededTables(): void
    {
        $tables = [
            'product_comment_likes',
            'store_comment_likes',
            'product_comments',
            'store_comments',
            'product_views',
            'product_likes',
            'offer_claims',
            'product_offer_variant_targets',
            'product_offers',
            'product_revisions',
            'product_variant_attributes',
            'product_variants',
            'product_images',
            'product_categories',
            'products',
            'store_invitations',
            'store_employees',
            'store_followers',
            'store_socials',
            'store_hours',
            'store_verifications',
            'store_store_category',
            'addressables',
            'addresses',
            'notifications',
            'notify_me',
            'contact_us_customer',
            'contact_us_seller',
            'interests',
            'shop_interests',
            'otps',
            'user_preferences',
            'profiles',
            'user_points',
            'sessions',
            'personal_access_tokens',
            'user_roles',
            'model_has_permissions',
            'model_has_roles',
            'role_has_permissions',
            'permissions',
            'roles',
            'stores',
            'socials',
            'store_categories',
            'categories',
            'users',
        ];

        Schema::disableForeignKeyConstraints();

        try {
            if (DB::getDriverName() === 'mysql') {
                DB::statement('SET FOREIGN_KEY_CHECKS=0');
            }

            foreach ($tables as $table) {
                if (Schema::hasTable($table)) {
                    DB::table($table)->delete();
                }
            }
        } finally {
            if (DB::getDriverName() === 'mysql') {
                DB::statement('SET FOREIGN_KEY_CHECKS=1');
            }

            Schema::enableForeignKeyConstraints();
        }
    }
}
