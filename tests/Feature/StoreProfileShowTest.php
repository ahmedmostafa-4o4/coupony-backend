<?php

namespace Tests\Feature;

use App\Domain\Store\Enums\StoreStatus;
use App\Domain\Store\Models\Store;
use App\Domain\Store\Models\StoreProfileView;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreProfileShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_store_returns_200_with_correct_json_structure(): void
    {
        $store = Store::factory()->active()->create();

        $response = $this->getJson("/api/v1/public-stores/{$store->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'name',
                    'description',
                    'logo_url',
                    'banner_url',
                    'email',
                    'phone',
                    'is_verified',
                    'rating_avg',
                    'rating_count',
                    'followers_count',
                    'categories',
                    'addresses',
                    'socials',
                    'hours',
                ],
            ])
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $store->id);
    }

    public function test_inactive_store_returns_404(): void
    {
        $store = Store::factory()->create([
            'status' => StoreStatus::PENDING,
        ]);

        $response = $this->getJson("/api/v1/public-stores/{$store->id}");

        $response->assertNotFound();
    }

    public function test_profile_view_is_recorded_after_successful_request(): void
    {
        $store = Store::factory()->active()->create();

        $this->assertDatabaseCount('store_profile_views', 0);

        $this->getJson("/api/v1/public-stores/{$store->id}");

        $this->assertDatabaseCount('store_profile_views', 1);
        $this->assertDatabaseHas('store_profile_views', [
            'store_id' => $store->id,
        ]);
    }

    public function test_authenticated_user_id_is_recorded_in_profile_view(): void
    {
        $user = User::factory()->create();
        $store = Store::factory()->active()->create();

        $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/public-stores/{$store->id}");

        $this->assertDatabaseHas('store_profile_views', [
            'store_id' => $store->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_unauthenticated_request_returns_200_and_records_view_with_null_user_id(): void
    {
        $store = Store::factory()->active()->create();

        $response = $this->getJson("/api/v1/public-stores/{$store->id}");

        $response->assertOk();

        $view = StoreProfileView::where('store_id', $store->id)->first();
        $this->assertNotNull($view);
        $this->assertNull($view->user_id);
    }

    public function test_multiple_requests_create_multiple_profile_view_records(): void
    {
        $store = Store::factory()->active()->create();

        $this->getJson("/api/v1/public-stores/{$store->id}");
        $this->getJson("/api/v1/public-stores/{$store->id}");
        $this->getJson("/api/v1/public-stores/{$store->id}");

        $this->assertDatabaseCount('store_profile_views', 3);
    }
}
