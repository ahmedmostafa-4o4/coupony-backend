<?php

namespace Tests\Feature\Admin;

use App\Domain\Store\Enums\StoreStatus;
use App\Domain\Store\Models\Store;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        Role::create(['name' => 'admin', 'guard_name' => 'sanctum']);
        Role::create(['name' => 'customer', 'guard_name' => 'sanctum']);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->customer = User::factory()->create();
        $this->customer->assignRole('customer');
    }

    public function test_admin_can_view_dashboard_overview()
    {
        // Setup dummy data
        User::factory()->count(2)->create();
        Store::factory()->count(3)->create([
            'status' => StoreStatus::ACTIVE,
            'total_sales' => 100,
            'rating_avg' => 4.5,
            'subscription_tier' => 'premium'
        ]);
        Store::factory()->create([
            'status' => StoreStatus::PENDING,
            'total_sales' => 0
        ]);

        DB::table('user_points')->insert([
            'user_id' => $this->customer->id,
            'current_balance' => 500,
            'lifetime_earned' => 1000,
            'lifetime_spent' => 500,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        DB::table('contact_us_customer')->insert([
            'name' => 'Test',
            'email' => 'test@example.com',
            'subject' => 'Test',
            'message' => 'Test Message',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/dashboard/overview');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'growth' => [
                        'total_users',
                        'total_stores',
                        'new_users_this_month',
                        'new_stores_this_month',
                    ],
                    'financial' => [
                        'total_sales_volume',
                        'premium_stores',
                        'average_store_rating',
                    ],
                    'points_economy' => [
                        'total_points_in_circulation',
                        'lifetime_points_earned',
                        'lifetime_points_spent',
                        'points_redemption_rate',
                    ],
                    'operational' => [
                        'pending_store_approvals',
                        'pending_verifications',
                        'unresolved_customer_tickets',
                        'unresolved_seller_tickets',
                    ],
                    'charts' => [
                        'user_growth',
                        'store_growth',
                        'claims_volume',
                        'subscription_distribution',
                        'top_stores',
                        'points_flow' => [
                            'earned',
                            'spent'
                        ]
                    ]
                ]
            ]);

        // Assert some specific data calculations
        $response->assertJsonPath('data.growth.total_users', 8); // admin + customer + 2 created + 4 implicit store owners
        $response->assertJsonPath('data.growth.total_stores', 3); // 3 active stores
        $response->assertJsonPath('data.financial.premium_stores', 3);
        $response->assertJsonPath('data.financial.total_sales_volume', 300);
        $response->assertJsonPath('data.points_economy.total_points_in_circulation', 500);
        $response->assertJsonPath('data.operational.pending_store_approvals', 1);
        $response->assertJsonPath('data.operational.unresolved_customer_tickets', 1);
    }

    public function test_non_admin_cannot_access_dashboard_overview()
    {
        $response = $this->actingAs($this->customer)
            ->getJson('/api/v1/admin/dashboard/overview');

        $response->assertStatus(403);
    }
}
