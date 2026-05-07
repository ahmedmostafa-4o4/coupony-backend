<?php

namespace Tests\Feature;

use App\Domain\Store\Enums\StoreStatus;
use App\Domain\Store\Models\Store;
use App\Domain\Store\Models\StoreFollowers;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StoreFollowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'customer', 'guard_name' => 'sanctum']);
        Role::create(['name' => 'seller', 'guard_name' => 'sanctum']);
        Role::create(['name' => 'admin', 'guard_name' => 'sanctum']);

        Config::set('logging.default', 'single');
    }

    // ──────────────────────────────────────────────
    //  Follow
    // ──────────────────────────────────────────────

    public function test_authenticated_user_can_follow_active_store(): void
    {
        $store = Store::factory()->active()->create(['followers_count' => 0]);
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/v1/public-stores/{$store->id}/follow");

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.store_id', $store->id)
            ->assertJsonPath('data.is_following', true)
            ->assertJsonPath('data.notification_enabled', true)
            ->assertJsonPath('data.followers_count', 1);

        $this->assertDatabaseHas('store_followers', [
            'store_id' => $store->id,
            'user_id' => $user->id,
            'notification_enabled' => true,
        ]);

        // Verify denormalized count
        $this->assertEquals(1, $store->fresh()->followers_count);
    }

    public function test_following_same_store_twice_is_idempotent(): void
    {
        $store = Store::factory()->active()->create(['followers_count' => 0]);
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        // Follow first time
        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/v1/public-stores/{$store->id}/follow");

        // Follow second time
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/v1/public-stores/{$store->id}/follow");

        $response->assertOk()
            ->assertJsonPath('data.is_following', true)
            ->assertJsonPath('data.followers_count', 1);

        // Still only 1 record
        $this->assertEquals(1, StoreFollowers::where('store_id', $store->id)->count());
        $this->assertEquals(1, $store->fresh()->followers_count);
    }

    public function test_cannot_follow_pending_store(): void
    {
        $store = Store::factory()->pending()->create();
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/v1/public-stores/{$store->id}/follow");

        $response->assertNotFound()
            ->assertJsonPath('success', false);
    }

    public function test_cannot_follow_suspended_store(): void
    {
        $store = Store::factory()->create(['status' => StoreStatus::SUSPENDED]);
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/v1/public-stores/{$store->id}/follow");

        $response->assertNotFound()
            ->assertJsonPath('success', false);
    }

    public function test_unauthenticated_user_cannot_follow_store(): void
    {
        $store = Store::factory()->active()->create();

        $response = $this->postJson("/api/v1/public-stores/{$store->id}/follow");

        $response->assertUnauthorized();
    }

    // ──────────────────────────────────────────────
    //  Unfollow
    // ──────────────────────────────────────────────

    public function test_authenticated_user_can_unfollow_store(): void
    {
        $store = Store::factory()->active()->create(['followers_count' => 1]);
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        StoreFollowers::create([
            'store_id' => $store->id,
            'user_id' => $user->id,
            'notification_enabled' => true,
            'followed_at' => now(),
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->deleteJson("/api/v1/public-stores/{$store->id}/follow");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.is_following', false)
            ->assertJsonPath('data.followers_count', 0);

        $this->assertDatabaseMissing('store_followers', [
            'store_id' => $store->id,
            'user_id' => $user->id,
        ]);

        $this->assertEquals(0, $store->fresh()->followers_count);
    }

    public function test_unfollow_when_not_following_returns_422(): void
    {
        $store = Store::factory()->active()->create();
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->deleteJson("/api/v1/public-stores/{$store->id}/follow");

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_unauthenticated_user_cannot_unfollow_store(): void
    {
        $store = Store::factory()->active()->create();

        $response = $this->deleteJson("/api/v1/public-stores/{$store->id}/follow");

        $response->assertUnauthorized();
    }

    // ──────────────────────────────────────────────
    //  Toggle notifications
    // ──────────────────────────────────────────────

    public function test_can_toggle_follow_notifications(): void
    {
        $store = Store::factory()->active()->create();
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        StoreFollowers::create([
            'store_id' => $store->id,
            'user_id' => $user->id,
            'notification_enabled' => true,
            'followed_at' => now(),
        ]);

        // Toggle off
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->patchJson("/api/v1/public-stores/{$store->id}/follow/notifications");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.notification_enabled', false);

        // Toggle back on
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->patchJson("/api/v1/public-stores/{$store->id}/follow/notifications");

        $response->assertOk()
            ->assertJsonPath('data.notification_enabled', true);
    }

    public function test_toggle_notifications_when_not_following_returns_422(): void
    {
        $store = Store::factory()->active()->create();
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->patchJson("/api/v1/public-stores/{$store->id}/follow/notifications");

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    // ──────────────────────────────────────────────
    //  List followed stores
    // ──────────────────────────────────────────────

    public function test_can_list_followed_stores(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $store1 = Store::factory()->active()->create(['followers_count' => 1]);
        $store2 = Store::factory()->active()->create(['followers_count' => 1]);

        StoreFollowers::create([
            'store_id' => $store1->id,
            'user_id' => $user->id,
            'notification_enabled' => true,
            'followed_at' => now()->subDay(),
        ]);

        StoreFollowers::create([
            'store_id' => $store2->id,
            'user_id' => $user->id,
            'notification_enabled' => false,
            'followed_at' => now(),
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/me/followed-stores');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure([
                'data' => [[
                    'id', 'name', 'description', 'logo_url', 'banner_url',
                    'is_verified', 'rating_avg', 'rating_count', 'followers_count',
                    'is_following', 'notification_enabled', 'followed_at',
                ]],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);

        // Most recently followed store should be first
        $this->assertEquals($store2->id, $response->json('data.0.id'));
        $this->assertEquals($store1->id, $response->json('data.1.id'));
    }

    public function test_followed_stores_excludes_non_active_stores(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $activeStore = Store::factory()->active()->create(['followers_count' => 1]);
        $pendingStore = Store::factory()->pending()->create(['followers_count' => 1]);

        StoreFollowers::create([
            'store_id' => $activeStore->id,
            'user_id' => $user->id,
            'notification_enabled' => true,
            'followed_at' => now(),
        ]);

        StoreFollowers::create([
            'store_id' => $pendingStore->id,
            'user_id' => $user->id,
            'notification_enabled' => true,
            'followed_at' => now(),
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/me/followed-stores');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $activeStore->id);
    }

    public function test_followed_stores_supports_pagination(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $stores = Store::factory()->active()->count(5)->create(['followers_count' => 1]);

        foreach ($stores as $store) {
            StoreFollowers::create([
                'store_id' => $store->id,
                'user_id' => $user->id,
                'notification_enabled' => true,
                'followed_at' => now(),
            ]);
        }

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/me/followed-stores?per_page=2');

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 5)
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonPath('meta.last_page', 3);
    }

    public function test_unauthenticated_user_cannot_list_followed_stores(): void
    {
        $response = $this->getJson('/api/v1/me/followed-stores');

        $response->assertUnauthorized();
    }

    // ──────────────────────────────────────────────
    //  Denormalized count consistency
    // ──────────────────────────────────────────────

    public function test_followers_count_stays_consistent_with_multiple_users(): void
    {
        $store = Store::factory()->active()->create(['followers_count' => 0]);
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();

        $token1 = $user1->createToken('test-token')->plainTextToken;
        $token2 = $user2->createToken('test-token')->plainTextToken;
        $token3 = $user3->createToken('test-token')->plainTextToken;

        // User 1 follows
        $r1 = $this->withHeader('Authorization', "Bearer {$token1}")
            ->postJson("/api/v1/public-stores/{$store->id}/follow");
        $r1->assertStatus(201);
        $this->assertEquals(1, $r1->json('data.followers_count'));

        // Reset auth guard so Sanctum resolves user2 from token2
        $this->app['auth']->forgetGuards();

        // User 2 follows
        $r2 = $this->withHeader('Authorization', "Bearer {$token2}")
            ->postJson("/api/v1/public-stores/{$store->id}/follow");
        $r2->assertStatus(201);
        $this->assertEquals(2, $r2->json('data.followers_count'));

        // Reset auth guard
        $this->app['auth']->forgetGuards();

        // User 3 follows
        $r3 = $this->withHeader('Authorization', "Bearer {$token3}")
            ->postJson("/api/v1/public-stores/{$store->id}/follow");
        $r3->assertStatus(201);
        $this->assertEquals(3, $r3->json('data.followers_count'));

        // Verify DB records
        $this->assertEquals(3, StoreFollowers::where('store_id', $store->id)->count());
        $this->assertEquals(3, $store->fresh()->followers_count);

        // Reset auth guard
        $this->app['auth']->forgetGuards();

        // User 2 unfollows
        $r4 = $this->withHeader('Authorization', "Bearer {$token2}")
            ->deleteJson("/api/v1/public-stores/{$store->id}/follow");
        $r4->assertOk();
        $this->assertEquals(2, $r4->json('data.followers_count'));

        $this->assertEquals(2, $store->fresh()->followers_count);
    }
}
