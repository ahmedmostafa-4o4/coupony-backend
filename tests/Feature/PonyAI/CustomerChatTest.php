<?php

namespace Tests\Feature\PonyAI;

use App\Domain\PonyAI\Contracts\GeminiClient;
use App\Domain\PonyAI\Models\PonyConversation;
use App\Domain\PonyAI\Models\PonyMessage;
use App\Domain\PonyAI\Repositories\EmbeddingRepository;
use App\Domain\PonyAI\Services\GeminiFakeClient;
use App\Domain\Product\Enums\ProductApprovalStatus;
use App\Domain\Product\Enums\ProductStatus;
use App\Domain\Product\Models\Product;
use App\Domain\Store\Models\Store;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerChatTest extends TestCase
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

    private function product(): Product
    {
        $store = Store::factory()->create([
            'owner_user_id' => User::factory()->create()->id,
        ]);

        return Product::factory()->create([
            'store_id' => $store->id,
            'title' => 'Sample Product',
            'status' => ProductStatus::ACTIVE,
            'approval_status' => ProductApprovalStatus::APPROVED,
        ]);
    }

    public function test_unauthenticated_requests_return_401(): void
    {
        $this->postJson('/api/v1/pony/customer/chat', ['message' => 'hi'])
            ->assertStatus(401);
    }

    public function test_authenticated_chat_persists_messages_and_returns_grounded_products(): void
    {
        $user = User::factory()->create();
        $product = $this->product();

        $this->app->make(EmbeddingRepository::class)
            ->upsertProductTextEmbedding($product->id, [1.0, 0.0], 1, 'm');

        $this->fake()
            ->queueJson([])
            ->queueEmbedding([1.0, 0.0])
            ->queueJson([
                'message' => 'Here is a sample product.',
                'product_ids' => [$product->id],
                'offer_ids' => [],
            ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/pony/customer/chat', ['message' => 'show me something']);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.message', 'Here is a sample product.')
            ->assertJsonPath('data.products.0.id', $product->id)
            ->assertJsonPath('data.conversation.persona', 'customer');

        $this->assertSame(1, PonyConversation::count());
        $this->assertSame(2, PonyMessage::count());
    }

    public function test_continuing_an_existing_conversation_appends_to_it(): void
    {
        $user = User::factory()->create();
        $this->product();

        $this->fake()
            ->queueJson([])->queueEmbedding([1.0])->queueJson(['message' => 'first', 'product_ids' => [], 'offer_ids' => []])
            ->queueJson([])->queueEmbedding([1.0])->queueJson(['message' => 'second', 'product_ids' => [], 'offer_ids' => []]);

        $first = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/pony/customer/chat', ['message' => 'hi']);

        $conversationId = $first->json('data.conversation.id');

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/pony/customer/chat', ['message' => 'hello again', 'conversation_id' => $conversationId])
            ->assertOk()
            ->assertJsonPath('data.conversation.id', $conversationId);

        $this->assertSame(1, PonyConversation::count());
        $this->assertSame(4, PonyMessage::count());
    }

    public function test_referencing_someone_elses_conversation_returns_404(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $this->product();

        $this->fake()->queueJson([])->queueEmbedding([1.0])->queueJson(['message' => 'ok', 'product_ids' => [], 'offer_ids' => []]);

        $first = $this->actingAs($owner, 'sanctum')
            ->postJson('/api/v1/pony/customer/chat', ['message' => 'hi']);

        $conversationId = $first->json('data.conversation.id');

        $this->actingAs($intruder, 'sanctum')
            ->postJson('/api/v1/pony/customer/chat', ['message' => 'sneaky', 'conversation_id' => $conversationId])
            ->assertStatus(404);
    }

    public function test_message_validation(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/pony/customer/chat', ['message' => ''])
            ->assertStatus(422);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/pony/customer/chat', ['message' => str_repeat('a', 2001)])
            ->assertStatus(422);
    }

    public function test_index_lists_only_my_conversations(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $this->product();

        $this->fake()
            ->queueJson([])->queueEmbedding([1.0])->queueJson(['message' => 'a', 'product_ids' => [], 'offer_ids' => []])
            ->queueJson([])->queueEmbedding([1.0])->queueJson(['message' => 'b', 'product_ids' => [], 'offer_ids' => []]);

        $this->actingAs($owner, 'sanctum')
            ->postJson('/api/v1/pony/customer/chat', ['message' => 'hi'])
            ->assertOk();
        $this->actingAs($other, 'sanctum')
            ->postJson('/api/v1/pony/customer/chat', ['message' => 'hi'])
            ->assertOk();

        $this->actingAs($owner, 'sanctum')
            ->getJson('/api/v1/pony/customer/conversations')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.total', 1);
    }

    public function test_show_returns_messages_for_owner_only(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $this->product();

        $this->fake()->queueJson([])->queueEmbedding([1.0])->queueJson(['message' => 'a', 'product_ids' => [], 'offer_ids' => []]);

        $conversationId = $this->actingAs($owner, 'sanctum')
            ->postJson('/api/v1/pony/customer/chat', ['message' => 'hi'])
            ->json('data.conversation.id');

        $this->actingAs($owner, 'sanctum')
            ->getJson("/api/v1/pony/customer/conversations/{$conversationId}")
            ->assertOk()
            ->assertJsonPath('data.id', $conversationId)
            ->assertJsonCount(2, 'data.messages');

        $this->actingAs($intruder, 'sanctum')
            ->getJson("/api/v1/pony/customer/conversations/{$conversationId}")
            ->assertStatus(404);
    }

    public function test_destroy_soft_deletes_the_conversation(): void
    {
        $user = User::factory()->create();
        $this->product();

        $this->fake()->queueJson([])->queueEmbedding([1.0])->queueJson(['message' => 'x', 'product_ids' => [], 'offer_ids' => []]);

        $conversationId = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/pony/customer/chat', ['message' => 'hi'])
            ->json('data.conversation.id');

        $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/v1/pony/customer/conversations/{$conversationId}")
            ->assertOk();

        $this->assertSoftDeleted('pony_conversations', ['id' => $conversationId]);

        $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/pony/customer/conversations/{$conversationId}")
            ->assertStatus(404);
    }
}
