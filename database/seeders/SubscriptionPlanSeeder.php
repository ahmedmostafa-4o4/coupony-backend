<?php

namespace Database\Seeders;

use App\Domain\Subscription\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Basic',
                'slug' => 'basic',
                'description' => 'Perfect for small stores just getting started. Includes essential features to manage your products and team.',
                'price_monthly' => 99.00,
                'price_yearly' => 999.00,
                'currency' => 'EGP',
                'max_products' => 50,
                'max_employees' => 5,
                'max_branches' => 3,
                'max_ai_messages_per_day' => 15,
                'features' => json_encode([
                    'ai_assistant' => false,
                    'analytics' => false,
                    'priority_support' => false,
                    'custom_branding' => false,
                    'bulk_import' => false,
                ]),
                'grace_period_days' => 7,
                'degraded_period_days' => 14,
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Premium',
                'slug' => 'premium',
                'description' => 'Ideal for growing stores that need advanced tools. Includes AI assistant, analytics, and higher limits.',
                'price_monthly' => 199.00,
                'price_yearly' => 1999.00,
                'currency' => 'EGP',
                'max_products' => 200,
                'max_employees' => 15,
                'max_branches' => 10,
                'max_ai_messages_per_day' => 30,
                'features' => json_encode([
                    'ai_assistant' => true,
                    'analytics' => true,
                    'priority_support' => true,
                    'custom_branding' => false,
                    'bulk_import' => true,
                ]),
                'grace_period_days' => 7,
                'degraded_period_days' => 14,
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'description' => 'For large-scale operations that need maximum capacity and all features unlocked.',
                'price_monthly' => 499.00,
                'price_yearly' => 4999.00,
                'currency' => 'EGP',
                'max_products' => 9999,
                'max_employees' => 50,
                'max_branches' => 25,
                'max_ai_messages_per_day' => 60,
                'features' => json_encode([
                    'ai_assistant' => true,
                    'analytics' => true,
                    'priority_support' => true,
                    'custom_branding' => true,
                    'bulk_import' => true,
                ]),
                'grace_period_days' => 14,
                'degraded_period_days' => 21,
                'is_active' => true,
                'sort_order' => 3,
            ],
        ];

        foreach ($plans as $plan) {
            SubscriptionPlan::query()->updateOrCreate(
                ['slug' => $plan['slug']],
                $plan
            );
        }

        $this->command->info(count($plans).' subscription plans seeded.');
    }
}
