<?php

namespace Tests\Feature;

use App\Domain\Store\Enums\StoreStatus;
use App\Domain\Store\Models\Store;
use App\Domain\Store\Models\StoreComment;
use App\Domain\Store\Models\StoreCommentLike;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StoreCommentTest extends TestCase
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
    //  Listing
    // ──────────────────────────────────────────────

    public function test_can_list_store_comments_on_active_store(): void
    {
        $store = Store::factory()->active()->create();
        $user = User::factory()->create();

        $review = StoreComment::create([
            'store_id' => $store->id,
            'user_id' => $user->id,
            'rating' => 4,
            'body' => 'Great store!',
        ]);

        $response = $this->getJson("/api/v1/public-stores/{$store->id}/comments");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.id', $review->id)
            ->assertJsonPath('data.0.rating', 4)
            ->assertJsonPath('data.0.body', 'Great store!')
            ->assertJsonStructure([
                'data' => [['id', 'store_id', 'rating', 'body', 'user', 'likes_count', 'is_liked', 'created_at']],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);
    }

    public function test_cannot_list_comments_on_non_active_store(): void
    {
        $store = Store::factory()->pending()->create();

        $response = $this->getJson("/api/v1/public-stores/{$store->id}/comments");

        $response->assertNotFound()
            ->assertJsonPath('success', false);
    }

    public function test_can_get_store_review_summary_on_active_store(): void
    {
        $store = Store::factory()->active()->create(['rating_avg' => 0, 'rating_count' => 0]);

        $users = User::factory()->count(5)->create();

        StoreComment::create([
            'store_id' => $store->id,
            'user_id' => $users[0]->id,
            'rating' => 5,
            'body' => 'Excellent',
        ]);

        StoreComment::create([
            'store_id' => $store->id,
            'user_id' => $users[1]->id,
            'rating' => 5,
            'body' => 'Perfect',
        ]);

        StoreComment::create([
            'store_id' => $store->id,
            'user_id' => $users[2]->id,
            'rating' => 4,
            'body' => 'Very good',
        ]);

        StoreComment::create([
            'store_id' => $store->id,
            'user_id' => $users[3]->id,
            'rating' => 3,
            'body' => 'Good',
        ]);

        StoreComment::create([
            'store_id' => $store->id,
            'user_id' => $users[4]->id,
            'rating' => 1,
            'body' => 'Bad',
        ]);

        $response = $this->getJson("/api/v1/public-stores/{$store->id}/reviews-summary");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.avg_rating', 3.6)
            ->assertJsonPath('data.rating_count', 5)
            ->assertJsonPath('data.five_star_count', 2)
            ->assertJsonPath('data.four_star_count', 1)
            ->assertJsonPath('data.three_star_count', 1)
            ->assertJsonPath('data.two_star_count', 0)
            ->assertJsonPath('data.one_star_count', 1)
            ->assertJsonPath('data.ratings_breakdown.5', 2)
            ->assertJsonPath('data.ratings_breakdown.4', 1)
            ->assertJsonPath('data.ratings_breakdown.3', 1)
            ->assertJsonPath('data.ratings_breakdown.2', 0)
            ->assertJsonPath('data.ratings_breakdown.1', 1);
    }

    // ──────────────────────────────────────────────
    //  Create review
    // ──────────────────────────────────────────────

    public function test_authenticated_user_can_create_store_review(): void
    {
        $store = Store::factory()->active()->create(['rating_avg' => 0, 'rating_count' => 0]);
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/v1/public-stores/{$store->id}/comments", [
                'rating' => 5,
                'body' => 'Amazing store!',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.rating', 5)
            ->assertJsonPath('data.body', 'Amazing store!');

        $this->assertDatabaseHas('store_comments', [
            'store_id' => $store->id,
            'user_id' => $user->id,
            'rating' => 5,
        ]);
    }

    public function test_unauthenticated_user_cannot_create_store_review(): void
    {
        $store = Store::factory()->active()->create();

        $response = $this->postJson("/api/v1/public-stores/{$store->id}/comments", [
            'rating' => 5,
            'body' => 'Should fail',
        ]);

        $response->assertUnauthorized();
    }

    public function test_cannot_create_review_on_inactive_store(): void
    {
        $store = Store::factory()->pending()->create();
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/v1/public-stores/{$store->id}/comments", [
                'rating' => 5,
                'body' => 'Should fail',
            ]);

        $response->assertNotFound();
    }

    public function test_duplicate_review_returns_422(): void
    {
        $store = Store::factory()->active()->create();
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        StoreComment::create([
            'store_id' => $store->id,
            'user_id' => $user->id,
            'rating' => 4,
            'body' => 'First review',
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/v1/public-stores/{$store->id}/comments", [
                'rating' => 3,
                'body' => 'Second review',
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    // ──────────────────────────────────────────────
    //  Reply
    // ──────────────────────────────────────────────

    public function test_authenticated_user_can_reply_to_review(): void
    {
        $store = Store::factory()->active()->create();
        $reviewer = User::factory()->create();
        $replier = User::factory()->create();
        $token = $replier->createToken('test-token')->plainTextToken;

        $review = StoreComment::create([
            'store_id' => $store->id,
            'user_id' => $reviewer->id,
            'rating' => 4,
            'body' => 'Nice store',
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/v1/public-stores/{$store->id}/comments/{$review->id}/replies", [
                'body' => 'Thank you!',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.body', 'Thank you!')
            ->assertJsonPath('data.parent_id', $review->id);

        $this->assertNull($response->json('data.rating'));
    }

    // ──────────────────────────────────────────────
    //  Update
    // ──────────────────────────────────────────────

    public function test_owner_can_update_own_comment(): void
    {
        $store = Store::factory()->active()->create();
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $review = StoreComment::create([
            'store_id' => $store->id,
            'user_id' => $user->id,
            'rating' => 3,
            'body' => 'OK store',
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->patchJson("/api/v1/store-comments/{$review->id}", [
                'rating' => 5,
                'body' => 'Actually great!',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.rating', 5)
            ->assertJsonPath('data.body', 'Actually great!');
    }

    // ──────────────────────────────────────────────
    //  Delete
    // ──────────────────────────────────────────────

    public function test_owner_can_delete_own_comment(): void
    {
        $store = Store::factory()->active()->create();
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $review = StoreComment::create([
            'store_id' => $store->id,
            'user_id' => $user->id,
            'rating' => 2,
            'body' => 'Not great',
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->deleteJson("/api/v1/store-comments/{$review->id}");

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('store_comments', ['id' => $review->id]);
    }

    // ──────────────────────────────────────────────
    //  Admin hide
    // ──────────────────────────────────────────────

    public function test_admin_can_hide_store_comment(): void
    {
        $store = Store::factory()->active()->create();
        $user = User::factory()->create();
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $token = $admin->createToken('test-token')->plainTextToken;

        $review = StoreComment::create([
            'store_id' => $store->id,
            'user_id' => $user->id,
            'rating' => 1,
            'body' => 'Spam content',
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->patchJson("/api/v1/admin/store-comments/{$review->id}/hide");

        $response->assertOk()
            ->assertJsonPath('data.status', 'hidden');

        $this->assertDatabaseHas('store_comments', [
            'id' => $review->id,
            'status' => 'hidden',
            'hidden_by' => $admin->id,
        ]);
    }

    public function test_owner_can_hide_store_comment(): void
    {
        $owner = User::factory()->create();
        $store = Store::factory()->active()->create(['owner_user_id' => $owner->id]);
        $user = User::factory()->create();
        $token = $owner->createToken('test-token')->plainTextToken;

        $review = StoreComment::create([
            'store_id' => $store->id,
            'user_id' => $user->id,
            'rating' => 1,
            'body' => 'Spam content',
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->patchJson("/api/v1/store-comments/{$review->id}/hide");

        $response->assertOk()
            ->assertJsonPath('data.status', 'hidden');

        $this->assertDatabaseHas('store_comments', [
            'id' => $review->id,
            'status' => 'hidden',
            'hidden_by' => $owner->id,
        ]);
    }

    // ──────────────────────────────────────────────
    //  Like / Unlike
    // ──────────────────────────────────────────────

    public function test_authenticated_user_can_like_store_comment(): void
    {
        $store = Store::factory()->active()->create();
        $reviewer = User::factory()->create();
        $liker = User::factory()->create();
        $token = $liker->createToken('test-token')->plainTextToken;

        $review = StoreComment::create([
            'store_id' => $store->id,
            'user_id' => $reviewer->id,
            'rating' => 5,
            'body' => 'Best store ever',
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/v1/store-comments/{$review->id}/likes");

        $response->assertOk()
            ->assertJsonPath('data.is_liked', true)
            ->assertJsonPath('data.likes_count', 1);

        $this->assertDatabaseHas('store_comment_likes', [
            'comment_id' => $review->id,
            'user_id' => $liker->id,
        ]);
    }

    public function test_authenticated_user_can_unlike_store_comment(): void
    {
        $store = Store::factory()->active()->create();
        $reviewer = User::factory()->create();
        $liker = User::factory()->create();
        $token = $liker->createToken('test-token')->plainTextToken;

        $review = StoreComment::create([
            'store_id' => $store->id,
            'user_id' => $reviewer->id,
            'rating' => 5,
            'body' => 'Best store ever',
        ]);

        StoreCommentLike::create([
            'comment_id' => $review->id,
            'user_id' => $liker->id,
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->deleteJson("/api/v1/store-comments/{$review->id}/likes");

        $response->assertOk()
            ->assertJsonPath('data.is_liked', false)
            ->assertJsonPath('data.likes_count', 0);
    }

    // ──────────────────────────────────────────────
    //  Rating aggregation
    // ──────────────────────────────────────────────

    public function test_store_rating_updates_after_review_creation(): void
    {
        $store = Store::factory()->active()->create(['rating_avg' => 0, 'rating_count' => 0]);

        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        StoreComment::create([
            'store_id' => $store->id,
            'user_id' => $user1->id,
            'rating' => 4,
        ]);

        $store->refresh();
        $this->assertEquals(4.00, (float) $store->rating_avg);
        $this->assertEquals(1, $store->rating_count);

        StoreComment::create([
            'store_id' => $store->id,
            'user_id' => $user2->id,
            'rating' => 2,
        ]);

        $store->refresh();
        $this->assertEquals(3.00, (float) $store->rating_avg);
        $this->assertEquals(2, $store->rating_count);
    }

    public function test_store_rating_updates_after_review_deletion(): void
    {
        $store = Store::factory()->active()->create(['rating_avg' => 0, 'rating_count' => 0]);
        $user = User::factory()->create();

        $review = StoreComment::create([
            'store_id' => $store->id,
            'user_id' => $user->id,
            'rating' => 5,
        ]);

        $store->refresh();
        $this->assertEquals(1, $store->rating_count);

        $review->delete();

        $store->refresh();
        $this->assertEquals(0, $store->rating_count);
        $this->assertEquals(0.00, (float) $store->rating_avg);
    }
}
