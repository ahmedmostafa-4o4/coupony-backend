<?php

namespace Tests\Unit;

use Database\Seeders\DatabaseSeeder;
use Database\Seeders\LargeDemoSeeder;
use Database\Seeders\ProductCategorySeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\SocialSeeder;
use Database\Seeders\StoreCategorySeeder;
use Database\Seeders\SubscriptionPlanSeeder;
use ReflectionClass;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    public function test_default_database_seeder_only_contains_production_reference_seeders(): void
    {
        $seeder = new class extends DatabaseSeeder
        {
            public array $calledSeeders = [];

            public function call($class, $silent = false, array $parameters = [])
            {
                $this->calledSeeders = array_merge($this->calledSeeders, (array) $class);

                return $this;
            }
        };

        $seeder->run();

        $this->assertSame([
            RoleAndPermissionSeeder::class,
            'Database\\Seeders\\AdminUserSeeder',
            StoreCategorySeeder::class,
            ProductCategorySeeder::class,
            SocialSeeder::class,
            SubscriptionPlanSeeder::class,
        ], $seeder->calledSeeders);
        $this->assertNotContains(LargeDemoSeeder::class, $seeder->calledSeeders);

        $databaseSeeder = new ReflectionClass(DatabaseSeeder::class);
        $this->assertFalse($databaseSeeder->hasMethod('cleanSeededTables'));
        $this->assertFalse($databaseSeeder->hasMethod('guardFreshDemoSeed'));
    }
}
