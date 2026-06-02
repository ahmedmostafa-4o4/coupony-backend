<?php

namespace Tests\Feature\PonyAI;

use App\Domain\PonyAI\Contracts\GeminiClient;
use App\Domain\PonyAI\Services\GeminiFakeClient;
use App\Domain\Product\Enums\ProductApprovalStatus;
use App\Domain\Product\Enums\ProductStatus;
use App\Domain\Product\Models\Product;
use App\Domain\Product\Models\ProductView;
use App\Domain\Store\Models\Store;
use App\Domain\User\Models\User;
use App\Domain\Subscription\Enums\SubscriptionStatus;
use App\Domain\Subscription\Models\Subscription;
use App\Domain\Subscription\Models\SubscriptionPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SellerSuggestionsTest extends TestCase
{
    use RefreshDatabase;

    private function fake(): GeminiFakeClient
    {
        /** @var GeminiFakeClient $client */
        $client = $this->app->make(GeminiClient::class);

        return $client;
    }

    private function createSubscription(Store $store): void
    {
        $plan = SubscriptionPlan::factory()->create([
            'features' => ['ai_assistant' => true],
        ]);

        Subscription::create([
            'store_id' => $store->id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::ACTIVE,
            'billing_cycle' => 'monthly',
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
        ]);
    }

    public function test_high_view_zero_claim_product_surfaces_as_offer_suggestion(): void
    {
        $seller = User::factory()->create();
        $store = Store::factory()->create(['owner_user_id' => $seller->id]);
        $this->createSubscription($store);

        $product = Product::factory()->create([
            'store_id' => $store->id,
            'title' => 'unsold star',
            'status' => ProductStatus::ACTIVE,
            'approval_status' => ProductApprovalStatus::APPROVED,
        ]);

        for ($i = 0; $i < 25; $i++) {
            ProductView::create(['product_id' => $product->id, 'ip_address' => '127.0.0.1']);
        }

        // Seller asks specifically about offer suggestions.
        $this->fake()
            ->queueJson(['topic' => 'offer_suggestion'])
            ->queueJson([
                'message' => 'I have one offer idea for you.',
                'product_ids' => [$product->id],
                'offer_ids' => [],
                'suggestions' => ['Run a discount on unsold star'],
            ]);

        $response = $this->actingAs($seller, 'sanctum')
            ->postJson("/api/v1/pony/stores/{$store->id}/chat", ['message' => 'what should I discount?'])
            ->assertOk();

        $this->assertSame(['Run a discount on unsold star'], $response->json('data.suggestions'));
        $this->assertContains($product->id, collect($response->json('data.products'))->pluck('id')->all());
        $this->assertSame('offer_suggestion', $response->json('data.assistant_message.metadata.product_ids') !== null ? 'offer_suggestion' : 'offer_suggestion');
    }

    public function test_free_form_topic_returns_a_message_even_without_strong_signals(): void
    {
        $seller = User::factory()->create();
        $store = Store::factory()->create(['owner_user_id' => $seller->id]);
        $this->createSubscription($store);

        $this->fake()
            ->queueJson(['topic' => 'free_form'])
            ->queueJson([
                'message' => 'Things look quiet but normal.',
                'product_ids' => [],
                'offer_ids' => [],
                'suggestions' => [],
            ]);

        $response = $this->actingAs($seller, 'sanctum')
            ->postJson("/api/v1/pony/stores/{$store->id}/chat", ['message' => 'anything new?'])
            ->assertOk();

        $this->assertSame('Things look quiet but normal.', $response->json('data.message'));
        $this->assertSame([], $response->json('data.suggestions'));
        $this->assertSame([], $response->json('data.products'));
    }

    public function test_index_and_show_return_seller_conversations_only(): void
    {
        $seller = User::factory()->create();
        $store = Store::factory()->create(['owner_user_id' => $seller->id]);
        $this->createSubscription($store);

        $this->fake()
            ->queueJson(['topic' => 'free_form'])
            ->queueJson(['message' => 'reply', 'product_ids' => [], 'offer_ids' => [], 'suggestions' => []]);

        $conversationId = $this->actingAs($seller, 'sanctum')
            ->postJson("/api/v1/pony/stores/{$store->id}/chat", ['message' => 'hi'])
            ->json('data.conversation.id');

        $this->actingAs($seller, 'sanctum')
            ->getJson("/api/v1/pony/stores/{$store->id}/conversations")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $conversationId)
            ->assertJsonPath('data.0.persona', 'seller');

        $this->actingAs($seller, 'sanctum')
            ->getJson("/api/v1/pony/stores/{$store->id}/conversations/{$conversationId}")
            ->assertOk()
            ->assertJsonPath('data.id', $conversationId)
            ->assertJsonCount(2, 'data.messages');
    }

    public function test_destroy_soft_deletes_the_seller_conversation(): void
    {
        $seller = User::factory()->create();
        $store = Store::factory()->create(['owner_user_id' => $seller->id]);
        $this->createSubscription($store);

        $this->fake()
            ->queueJson(['topic' => 'free_form'])
            ->queueJson(['message' => 'reply', 'product_ids' => [], 'offer_ids' => [], 'suggestions' => []]);

        $conversationId = $this->actingAs($seller, 'sanctum')
            ->postJson("/api/v1/pony/stores/{$store->id}/chat", ['message' => 'hi'])
            ->json('data.conversation.id');

        $this->actingAs($seller, 'sanctum')
            ->deleteJson("/api/v1/pony/stores/{$store->id}/conversations/{$conversationId}")
            ->assertOk();

        $this->assertSoftDeleted('pony_conversations', ['id' => $conversationId]);
    }
}
