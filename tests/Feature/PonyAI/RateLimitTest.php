<?php

namespace Tests\Feature\PonyAI;

use App\Domain\PonyAI\Contracts\GeminiClient;
use App\Domain\PonyAI\Services\GeminiFakeClient;
use App\Domain\Product\Enums\ProductApprovalStatus;
use App\Domain\Product\Enums\ProductStatus;
use App\Domain\Product\Models\Product;
use App\Domain\Store\Models\Store;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class RateLimitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->setLocale('en');
        RateLimiter::clear('pony:text:user:any');
    }

    private function fake(): GeminiFakeClient
    {
        /** @var GeminiFakeClient $client */
        $client = $this->app->make(GeminiClient::class);

        return $client;
    }

    private function activeProduct(): Product
    {
        $store = Store::factory()->create([
            'owner_user_id' => User::factory()->create()->id,
        ]);

        return Product::factory()->create([
            'store_id' => $store->id,
            'status' => ProductStatus::ACTIVE,
            'approval_status' => ProductApprovalStatus::APPROVED,
        ]);
    }

    public function test_customer_chat_returns_429_after_text_limit_exceeded(): void
    {
        config()->set('pony.rate_limits.text.max_attempts', 2);
        config()->set('pony.rate_limits.text.decay_seconds', 60);

        $user = User::factory()->create();
        $this->activeProduct();

        // Queue enough fakes for 2 successful turns.
        for ($i = 0; $i < 2; $i++) {
            $this->fake()
                ->queueJson([])
                ->queueEmbedding([1.0])
                ->queueJson(['message' => 'ok', 'product_ids' => [], 'offer_ids' => []]);
        }

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/pony/customer/chat', ['message' => 'hi'])->assertOk();
        $this->actingAs($user, 'sanctum')->postJson('/api/v1/pony/customer/chat', ['message' => 'hi again'])->assertOk();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/pony/customer/chat', ['message' => 'overage']);
        $response->assertStatus(429)
            ->assertJsonPath('success', false)
            ->assertHeader('Retry-After');

        $this->assertGreaterThan(0, (int) $response->headers->get('Retry-After'));
    }

    public function test_image_search_uses_its_own_separate_bucket(): void
    {
        // Make text very strict but image generous so we can verify they're not the same bucket.
        config()->set('pony.rate_limits.text.max_attempts', 1);
        config()->set('pony.rate_limits.image.max_attempts', 5);

        $user = User::factory()->create();
        $this->activeProduct();

        // Burn the text budget.
        $this->fake()
            ->queueJson([])
            ->queueEmbedding([1.0])
            ->queueJson(['message' => 'ok', 'product_ids' => [], 'offer_ids' => []]);

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/pony/customer/chat', ['message' => 'hi'])->assertOk();
        $this->actingAs($user, 'sanctum')->postJson('/api/v1/pony/customer/chat', ['message' => 'over'])->assertStatus(429);

        // Image-search bucket should still be open.
        $this->fake()
            ->queueDescription(json_encode(['caption' => 'foo']))
            ->queueDescription('foo')
            ->queueEmbedding([1.0, 0.0])
            ->queueEmbedding([1.0, 0.0])
            ->queueJson(['message' => 'ok', 'product_ids' => [], 'offer_ids' => []]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/pony/customer/image-search', [
                'image' => UploadedFile::fake()->create('photo.jpg', 100, 'image/jpeg'),
            ])
            ->assertOk();
    }

    public function test_different_users_get_separate_buckets(): void
    {
        config()->set('pony.rate_limits.text.max_attempts', 1);

        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $this->activeProduct();

        for ($i = 0; $i < 2; $i++) {
            $this->fake()
                ->queueJson([])
                ->queueEmbedding([1.0])
                ->queueJson(['message' => 'ok', 'product_ids' => [], 'offer_ids' => []]);
        }

        $this->actingAs($userA, 'sanctum')->postJson('/api/v1/pony/customer/chat', ['message' => 'hi'])->assertOk();
        $this->actingAs($userB, 'sanctum')->postJson('/api/v1/pony/customer/chat', ['message' => 'hi'])->assertOk();

        // userA is now at the limit, userB is not.
        $this->actingAs($userA, 'sanctum')->postJson('/api/v1/pony/customer/chat', ['message' => 'over'])->assertStatus(429);
    }

    public function test_remaining_header_decreases_with_each_request(): void
    {
        config()->set('pony.rate_limits.text.max_attempts', 5);

        $user = User::factory()->create();
        $this->activeProduct();

        $this->fake()
            ->queueJson([])
            ->queueEmbedding([1.0])
            ->queueJson(['message' => 'ok', 'product_ids' => [], 'offer_ids' => []]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/pony/customer/chat', ['message' => 'hi'])
            ->assertOk();

        $this->assertSame('5', $response->headers->get('X-RateLimit-Limit'));
        $this->assertSame('4', $response->headers->get('X-RateLimit-Remaining'));
    }

    public function test_seller_chat_is_throttled_too(): void
    {
        config()->set('pony.rate_limits.text.max_attempts', 1);

        $seller = User::factory()->create();
        $store = Store::factory()->create(['owner_user_id' => $seller->id]);

        for ($i = 0; $i < 2; $i++) {
            $this->fake()
                ->queueJson(['topic' => 'free_form'])
                ->queueJson(['message' => 'ok', 'product_ids' => [], 'offer_ids' => [], 'suggestions' => []]);
        }

        $this->actingAs($seller, 'sanctum')
            ->postJson("/api/v1/pony/stores/{$store->id}/chat", ['message' => 'hi'])
            ->assertOk();

        $this->actingAs($seller, 'sanctum')
            ->postJson("/api/v1/pony/stores/{$store->id}/chat", ['message' => 'over'])
            ->assertStatus(429);
    }

    public function test_zero_max_attempts_disables_throttling(): void
    {
        config()->set('pony.rate_limits.text.max_attempts', 0);

        $user = User::factory()->create();
        $this->activeProduct();

        for ($i = 0; $i < 6; $i++) {
            $this->fake()
                ->queueJson([])
                ->queueEmbedding([1.0])
                ->queueJson(['message' => 'ok', 'product_ids' => [], 'offer_ids' => []]);

            $this->actingAs($user, 'sanctum')
                ->postJson('/api/v1/pony/customer/chat', ['message' => "msg-{$i}"])
                ->assertOk();
        }
    }
}
