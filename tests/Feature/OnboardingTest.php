<?php

namespace Tests\Feature;

use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OnboardingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'customer', 'guard_name' => 'sanctum']);
        Role::create(['name' => 'seller', 'guard_name' => 'sanctum']);
    }

    public function test_customer_onboarding_endpoint_returns_empty_state_when_not_completed(): void
    {
        $user = User::factory()->create();
        $user->assignRole('customer');

        $response = $this->authenticatedJson('GET', '/api/v1/on-boarding/customer', [], $user);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'interesting_offers' => [],
                    'shopping_style' => [],
                    'budget' => null,
                ],
                'is_onboarding_completed' => false,
            ]);
    }

    public function test_customer_onboarding_endpoint_returns_saved_data(): void
    {
        $user = User::factory()->create();
        $user->assignRole('customer');

        DB::table('interests')->insert([
            'user_id' => $user->id,
            'interesting_offers' => json_encode(['electronics', 'fashion']),
            'shopping_style' => json_encode(['online', 'best_discount']),
            'budget' => 'medium',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->authenticatedJson('GET', '/api/v1/on-boarding/customer', [], $user);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'interesting_offers' => ['electronics', 'fashion'],
                    'shopping_style' => ['online', 'best_discount'],
                    'budget' => 'medium',
                ],
                'is_onboarding_completed' => true,
            ]);
    }

    public function test_seller_onboarding_endpoint_returns_saved_data(): void
    {
        $user = User::factory()->create();
        $user->assignRole('seller');

        DB::table('shop_interests')->insert([
            'user_id' => $user->id,
            'target_audience' => 'new_customers',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->authenticatedJson('GET', '/api/v1/on-boarding/seller', [], $user);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'target_audience' => 'new_customers',
                    'customer_reach_method' => null,
                    'price_category' => null,
                    'best_offer_time' => null,
                ],
                'is_onboarding_completed' => true,
            ]);
    }
}
