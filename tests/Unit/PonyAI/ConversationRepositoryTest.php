<?php

namespace Tests\Unit\PonyAI;

use App\Domain\PonyAI\Enums\AssistantPersona;
use App\Domain\PonyAI\Enums\PonyMessageRole;
use App\Domain\PonyAI\Models\PonyConversation;
use App\Domain\PonyAI\Repositories\ConversationRepository;
use App\Domain\Store\Models\Store;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConversationRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private function repo(): ConversationRepository
    {
        return $this->app->make(ConversationRepository::class);
    }

    public function test_start_customer_creates_conversation_with_no_store(): void
    {
        $user = User::factory()->create();

        $conversation = $this->repo()->startCustomer($user, 'My search');

        $this->assertSame($user->id, $conversation->user_id);
        $this->assertSame(AssistantPersona::CUSTOMER, $conversation->persona);
        $this->assertNull($conversation->store_id);
        $this->assertSame('My search', $conversation->title);
    }

    public function test_start_seller_requires_store(): void
    {
        $user = User::factory()->create();
        $store = Store::factory()->create(['owner_user_id' => $user->id]);

        $conversation = $this->repo()->startSeller($user, $store);

        $this->assertSame($store->id, $conversation->store_id);
        $this->assertSame(AssistantPersona::SELLER, $conversation->persona);
    }

    public function test_find_for_user_scopes_to_owner(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $conversation = $this->repo()->startCustomer($owner);

        $this->assertNotNull($this->repo()->findForUser($conversation->id, $owner));
        $this->assertNull($this->repo()->findForUser($conversation->id, $other));
    }

    public function test_seller_lookup_requires_matching_store(): void
    {
        $seller = User::factory()->create();
        $storeA = Store::factory()->create(['owner_user_id' => $seller->id]);
        $storeB = Store::factory()->create(['owner_user_id' => $seller->id]);

        $conversation = $this->repo()->startSeller($seller, $storeA);

        $this->assertNotNull($this->repo()->findSellerConversationForStore($conversation->id, $seller, $storeA));
        $this->assertNull($this->repo()->findSellerConversationForStore($conversation->id, $seller, $storeB));
    }

    public function test_paginate_customer_returns_only_my_conversations(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $this->repo()->startCustomer($userA);
        $this->repo()->startCustomer($userA);
        $this->repo()->startCustomer($userB);

        $page = $this->repo()->paginateCustomer($userA);

        $this->assertSame(2, $page->total());
        foreach ($page->items() as $conversation) {
            $this->assertSame($userA->id, $conversation->user_id);
        }
    }

    public function test_append_message_updates_last_message_at(): void
    {
        $user = User::factory()->create();
        $conversation = $this->repo()->startCustomer($user);

        $this->assertNull($conversation->last_message_at);

        $message = $this->repo()->appendUserMessage($conversation, 'Hi Pony', ['image' => 'foo.png']);

        $this->assertSame(PonyMessageRole::USER, $message->role);
        $this->assertSame('Hi Pony', $message->content);
        $this->assertSame(['image' => 'foo.png'], $message->attachments);

        $conversation->refresh();
        $this->assertNotNull($conversation->last_message_at);
        $this->assertSame(
            $message->created_at->toIso8601String(),
            $conversation->last_message_at->toIso8601String(),
        );
    }

    public function test_append_assistant_message_stores_metadata(): void
    {
        $user = User::factory()->create();
        $conversation = $this->repo()->startCustomer($user);

        $message = $this->repo()->appendAssistantMessage(
            $conversation,
            'Here are 3 products',
            ['product_ids' => ['p-1', 'p-2', 'p-3'], 'latency_ms' => 412],
        );

        $this->assertSame(PonyMessageRole::ASSISTANT, $message->role);
        $this->assertSame(['p-1', 'p-2', 'p-3'], $message->metadata['product_ids']);
        $this->assertSame(412, $message->metadata['latency_ms']);
        $this->assertNull($message->attachments);
    }

    public function test_soft_delete_hides_from_paginate(): void
    {
        $user = User::factory()->create();
        $conversation = $this->repo()->startCustomer($user);

        $this->repo()->delete($conversation);

        $this->assertSame(0, $this->repo()->paginateCustomer($user)->total());
        $this->assertNotNull(PonyConversation::onlyTrashed()->find($conversation->id));
    }
}
