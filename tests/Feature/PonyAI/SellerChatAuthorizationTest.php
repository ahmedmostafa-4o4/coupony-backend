<?php

namespace Tests\Feature\PonyAI;

use App\Domain\PonyAI\Contracts\GeminiClient;
use App\Domain\PonyAI\Repositories\ConversationRepository;
use App\Domain\PonyAI\Services\GeminiFakeClient;
use App\Domain\Store\Models\Store;
use App\Domain\Store\Models\StoreEmployee;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SellerChatAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private function fake(): GeminiFakeClient
    {
        /** @var GeminiFakeClient $client */
        $client = $this->app->make(GeminiClient::class);

        return $client;
    }

    private function ownedStore(User $owner): Store
    {
        return Store::factory()->create(['owner_user_id' => $owner->id]);
    }

    private function queueHappyPath(): void
    {
        $this->fake()
            ->queueJson(['topic' => 'free_form'])
            ->queueJson(['message' => 'ok', 'product_ids' => [], 'offer_ids' => [], 'suggestions' => []]);
    }

    public function test_unauthenticated_returns_401(): void
    {
        $owner = User::factory()->create();
        $store = $this->ownedStore($owner);

        $this->postJson("/api/v1/pony/stores/{$store->id}/chat", ['message' => 'hi'])
            ->assertStatus(401);
    }

    public function test_owner_can_chat(): void
    {
        $owner = User::factory()->create();
        $store = $this->ownedStore($owner);

        $this->queueHappyPath();

        $this->actingAs($owner, 'sanctum')
            ->postJson("/api/v1/pony/stores/{$store->id}/chat", ['message' => 'how am I doing?'])
            ->assertOk()
            ->assertJsonPath('data.conversation.persona', 'seller')
            ->assertJsonPath('data.conversation.store_id', $store->id);
    }

    public function test_store_employee_can_chat(): void
    {
        $owner = User::factory()->create();
        $employee = User::factory()->create();
        $store = $this->ownedStore($owner);

        StoreEmployee::create([
            'store_id' => $store->id,
            'user_id' => $employee->id,
            'role' => 'cashier',
            'permissions' => [],
        ]);

        $this->queueHappyPath();

        $this->actingAs($employee, 'sanctum')
            ->postJson("/api/v1/pony/stores/{$store->id}/chat", ['message' => 'how am I doing?'])
            ->assertOk();
    }

    public function test_other_seller_cannot_chat(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $store = $this->ownedStore($owner);

        $this->actingAs($intruder, 'sanctum')
            ->postJson("/api/v1/pony/stores/{$store->id}/chat", ['message' => 'spy'])
            ->assertStatus(403);
    }

    public function test_owner_of_one_store_cannot_open_conversation_of_another(): void
    {
        $sellerA = User::factory()->create();
        $sellerB = User::factory()->create();
        $storeA = $this->ownedStore($sellerA);
        $storeB = $this->ownedStore($sellerB);

        // Seller A starts a conversation on store A.
        $this->queueHappyPath();
        $conversationId = $this->actingAs($sellerA, 'sanctum')
            ->postJson("/api/v1/pony/stores/{$storeA->id}/chat", ['message' => 'hi'])
            ->json('data.conversation.id');

        // Seller A tries to reference that conversation under store B (which they don't own).
        $this->actingAs($sellerA, 'sanctum')
            ->postJson("/api/v1/pony/stores/{$storeB->id}/chat", [
                'message' => 'sneaky',
                'conversation_id' => $conversationId,
            ])
            ->assertStatus(403); // policy blocks on store B before we even look up the conversation
    }

    public function test_seller_cannot_open_other_sellers_conversation_via_show(): void
    {
        $sellerA = User::factory()->create();
        $sellerB = User::factory()->create();
        $storeA = $this->ownedStore($sellerA);

        $conversation = $this->app->make(ConversationRepository::class)
            ->startSeller($sellerA, $storeA);

        $this->actingAs($sellerB, 'sanctum')
            ->getJson("/api/v1/pony/stores/{$storeA->id}/conversations/{$conversation->id}")
            ->assertStatus(403);
    }

    public function test_index_and_show_obey_policy(): void
    {
        $sellerA = User::factory()->create();
        $sellerB = User::factory()->create();
        $storeA = $this->ownedStore($sellerA);

        $this->actingAs($sellerB, 'sanctum')
            ->getJson("/api/v1/pony/stores/{$storeA->id}/conversations")
            ->assertStatus(403);
    }

    public function test_destroy_requires_seller_access(): void
    {
        $sellerA = User::factory()->create();
        $sellerB = User::factory()->create();
        $storeA = $this->ownedStore($sellerA);

        $conversation = $this->app->make(ConversationRepository::class)
            ->startSeller($sellerA, $storeA);

        $this->actingAs($sellerB, 'sanctum')
            ->deleteJson("/api/v1/pony/stores/{$storeA->id}/conversations/{$conversation->id}")
            ->assertStatus(403);

        $this->assertDatabaseHas('pony_conversations', [
            'id' => $conversation->id,
            'deleted_at' => null,
        ]);
    }
}
