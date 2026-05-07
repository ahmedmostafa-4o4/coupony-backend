<?php

namespace Tests\Feature\API\V1;

use App\Domain\Store\Enums\StoreStatus;
use App\Domain\Store\Models\Store;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StoreFollowControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_retrieve_followers_for_an_active_store(): void
    {
        $user = User::factory()->create();
        $store = Store::factory()->create(['status' => StoreStatus::ACTIVE]);

        $follower1 = User::factory()->create();
        $follower2 = User::factory()->create();
        $follower3 = User::factory()->create();

        $store->followerUsers()->attach($follower1->id, ['followed_at' => Carbon::now()->subDays(2)]);
        $store->followerUsers()->attach($follower2->id, ['followed_at' => Carbon::now()->subDays(1)]);
        $store->followerUsers()->attach($follower3->id, ['followed_at' => Carbon::now()]);

        $response = $this->actingAs($user)->getJson("/api/v1/public-stores/{$store->id}/followers");

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'avatar',
                        'followed_at',
                    ],
                ],
                'meta' => [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                ],
            ])
            ->assertJsonCount(3, 'data')
            ->assertJsonFragment(['id' => $follower3->id])
            ->assertJsonFragment(['id' => $follower2->id])
            ->assertJsonFragment(['id' => $follower1->id]);

        $this->assertEquals($follower3->id, $response->json('data.0.id'));
        $this->assertEquals($follower2->id, $response->json('data.1.id'));
        $this->assertEquals($follower1->id, $response->json('data.2.id'));
    }

    #[Test]
    public function it_returns_404_if_store_is_not_found(): void
    {
        $user = User::factory()->create();
        $nonExistentStoreId = '99999999-9999-9999-9999-999999999999';

        $response = $this->actingAs($user)->getJson("/api/v1/public-stores/{$nonExistentStoreId}/followers");

        $response->assertNotFound()
            ->assertJson([
                'success' => false,
                'message' => __('api.store_follow.store_not_found'),
            ]);
    }

    #[Test]
    public function it_returns_404_if_store_is_inactive(): void
    {
        $user = User::factory()->create();
        $store = Store::factory()->create(['status' => StoreStatus::SUSPENDED]);

        $response = $this->actingAs($user)->getJson("/api/v1/public-stores/{$store->id}/followers");

        $response->assertNotFound()
            ->assertJson([
                'success' => false,
                'message' => __('api.store_follow.store_not_found'),
            ]);
    }

    #[Test]
    public function it_returns_empty_data_if_store_has_no_followers(): void
    {
        $user = User::factory()->create();
        $store = Store::factory()->create(['status' => StoreStatus::ACTIVE]);

        $response = $this->actingAs($user)->getJson("/api/v1/public-stores/{$store->id}/followers");

        $response->assertOk()
            ->assertJsonCount(0, 'data')
            ->assertJson([
                'success' => true,
                'message' => __('api.store_follow.followers_retrieved'),
                'data' => [],
                'meta' => [
                    'current_page' => 1,
                    'last_page' => 1,
                    'per_page' => 15,
                    'total' => 0,
                ],
            ]);
    }

    #[Test]
    public function it_applies_pagination_correctly(): void
    {
        $user = User::factory()->create();
        $store = Store::factory()->create(['status' => StoreStatus::ACTIVE]);

        for ($i = 0; $i < 20; $i++) {
            $follower = User::factory()->create();
            $store->followerUsers()->attach($follower->id, ['followed_at' => Carbon::now()->subMinutes(20 - $i)]);
        }

        $response = $this->actingAs($user)->getJson("/api/v1/public-stores/{$store->id}/followers?per_page=5");

        $response->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJson([
                'success' => true,
                'message' => __('api.store_follow.followers_retrieved'),
                'meta' => [
                    'current_page' => 1,
                    'last_page' => 4,
                    'per_page' => 5,
                    'total' => 20,
                ],
            ]);
    }
}
