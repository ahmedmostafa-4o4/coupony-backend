<?php

namespace Tests\Unit\PonyAI;

use App\Domain\PonyAI\Contracts\GeminiClient;
use App\Domain\PonyAI\Enums\AssistantPersona;
use App\Domain\PonyAI\Enums\PonyMessageRole;
use App\Domain\PonyAI\Repositories\EmbeddingRepository;
use App\Domain\PonyAI\Services\CustomerAssistantStrategy;
use App\Domain\PonyAI\Services\GeminiFakeClient;
use App\Domain\Product\Enums\ProductApprovalStatus;
use App\Domain\Product\Enums\ProductStatus;
use App\Domain\Product\Models\Product;
use App\Domain\Store\Models\Store;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerAssistantStrategyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->setLocale('en');
    }

    private function fake(): GeminiFakeClient
    {
        /** @var GeminiFakeClient $client */
        $client = $this->app->make(GeminiClient::class);

        return $client;
    }

    private function activeProduct(string $title): Product
    {
        $store = Store::factory()->create([
            'owner_user_id' => User::factory()->create()->id,
        ]);

        return Product::factory()->create([
            'store_id' => $store->id,
            'title' => $title,
            'short_description' => "Looking for a {$title}?",
            'status' => ProductStatus::ACTIVE,
            'approval_status' => ProductApprovalStatus::APPROVED,
        ]);
    }

    public function test_full_pipeline_returns_grounded_products_and_persists_messages(): void
    {
        $user = User::factory()->create();
        $wallet = $this->activeProduct('leather wallet');
        $other = $this->activeProduct('leather wallet two');

        $repo = $this->app->make(EmbeddingRepository::class);
        $repo->upsertProductTextEmbedding($wallet->id, [1.0, 0.0], 1, 'm');
        $repo->upsertProductTextEmbedding($other->id, [0.0, 1.0], 1, 'm');

        $this->fake()
            ->queueJson([])                                       // IntentExtractor
            ->queueEmbedding([1.0, 0.0])                          // EmbeddingReranker query embedding
            ->queueJson([                                          // AnswerComposer
                'message' => 'I found a great leather wallet for you.',
                'product_ids' => [$wallet->id],
                'offer_ids' => [],
            ]);

        $reply = $this->app->make(CustomerAssistantStrategy::class)
            ->handle($user, 'I need a leather wallet');

        $this->assertSame('I found a great leather wallet for you.', $reply->message);
        $this->assertCount(1, $reply->groundedProducts);
        $this->assertSame($wallet->id, $reply->groundedProducts->first()->id);

        $this->assertSame(PonyMessageRole::USER, $reply->userMessage->role);
        $this->assertSame(PonyMessageRole::ASSISTANT, $reply->assistantMessage->role);
        $this->assertSame([$wallet->id], $reply->assistantMessage->metadata['product_ids']);

        $this->assertSame($user->id, $reply->conversation->user_id);
        $this->assertSame(AssistantPersona::CUSTOMER, $reply->conversation->persona);
        $this->assertNotNull($reply->conversation->last_message_at);
    }

    public function test_invented_product_ids_are_dropped_and_recorded(): void
    {
        $user = User::factory()->create();
        $real = $this->activeProduct('headphones');

        $this->fake()
            ->queueJson([])
            ->queueEmbedding([1.0, 0.0])
            ->queueJson([
                'message' => 'Try these.',
                'product_ids' => ['hallucinated-uuid-not-in-db', $real->id, 'another-fake'],
                'offer_ids' => [],
            ]);

        $reply = $this->app->make(CustomerAssistantStrategy::class)
            ->handle($user, 'I want bluetooth headphones');

        $this->assertSame([$real->id], $reply->groundedProducts->pluck('id')->all());
        $this->assertSame(
            ['hallucinated-uuid-not-in-db', 'another-fake'],
            $reply->droppedProductIds,
        );
    }

    public function test_existing_conversation_is_reused(): void
    {
        $user = User::factory()->create();
        $product = $this->activeProduct('socks');

        $this->fake()
            ->queueJson([])->queueEmbedding([1.0])->queueJson(['message' => 'first', 'product_ids' => [], 'offer_ids' => []])
            ->queueJson([])->queueEmbedding([1.0])->queueJson(['message' => 'second', 'product_ids' => [], 'offer_ids' => []]);

        $strategy = $this->app->make(CustomerAssistantStrategy::class);
        $first = $strategy->handle($user, 'hi');
        $second = $strategy->handle($user, 'hello again', $first->conversation);

        $this->assertSame($first->conversation->id, $second->conversation->id);
        $this->assertSame(4, $first->conversation->fresh()->messages()->count());
    }

    public function test_no_candidates_still_returns_a_message_and_persists_assistant_turn(): void
    {
        $user = User::factory()->create();

        $this->fake()
            ->queueJson([])
            ->queueJson([
                'message' => 'I could not find anything.',
                'product_ids' => [],
                'offer_ids' => [],
            ]);

        $reply = $this->app->make(CustomerAssistantStrategy::class)
            ->handle($user, 'rare unicorn item that does not exist');

        $this->assertCount(0, $reply->groundedProducts);
        $this->assertNotSame('', $reply->message);
        $this->assertSame(PonyMessageRole::ASSISTANT, $reply->assistantMessage->role);
    }
}
