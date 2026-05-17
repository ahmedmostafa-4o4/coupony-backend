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
use Tests\TestCase;

class ArabicCustomerSearchTest extends TestCase
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

    private function activeProduct(string $title, array $extra = []): Product
    {
        $store = Store::factory()->create([
            'owner_user_id' => User::factory()->create()->id,
        ]);

        return Product::factory()->create(array_merge([
            'store_id' => $store->id,
            'title' => $title,
            'short_description' => "Looking for {$title}?",
            'status' => ProductStatus::ACTIVE,
            'approval_status' => ProductApprovalStatus::APPROVED,
        ], $extra));
    }

    /** Queue a successful turn that picks every candidate the strategy passes to Gemini. */
    private function queueIntentAndPickAll(array $intent): void
    {
        $this->fake()
            ->queueJson($intent)
            // EmbeddingReranker query embedding (only used if there are stored embeddings).
            ->queueEmbedding([1.0, 0.0]);
    }

    private function queueComposerReturn(array $productIds): void
    {
        $this->fake()->queueJson([
            'message' => 'Here are some matches.',
            'product_ids' => $productIds,
            'offer_ids' => [],
        ]);
    }

    /**
     * a) Generic Arabic catalog prompt returns active/approved products.
     */
    public function test_generic_arabic_prompt_returns_catalog(): void
    {
        $user = User::factory()->create();
        $a = $this->activeProduct('Nike Sneakers');
        $b = $this->activeProduct('Leather Wallet');

        $this->queueIntentAndPickAll([
            'is_generic_catalog_request' => true,
            'keywords' => [],
        ]);
        $this->queueComposerReturn([$a->id, $b->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/pony/customer/chat', ['message' => 'هل يوجد منتجات'])
            ->assertOk();

        $ids = collect($response->json('data.products'))->pluck('id')->all();
        $this->assertEqualsCanonicalizing([$a->id, $b->id], $ids);
    }

    /**
     * b) A different generic Arabic prompt also returns products.
     */
    public function test_show_available_products_arabic_returns_catalog(): void
    {
        $user = User::factory()->create();
        $product = $this->activeProduct('Headphones');

        $this->queueIntentAndPickAll([
            'is_generic_catalog_request' => true,
            'keywords' => [],
        ]);
        $this->queueComposerReturn([$product->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/pony/customer/chat', ['message' => 'اعرض المنتجات المتاحة'])
            ->assertOk();

        $this->assertSame([$product->id], collect($response->json('data.products'))->pluck('id')->all());
    }

    /**
     * c) "عايز كوتشي" can match an English "Nike Sneakers" title because the
     *    intent extractor expands the Arabic slang into English-equivalent keywords.
     *    The retriever no longer applies a WHERE-LIKE filter, so the SQL set is
     *    not zeroed out even though the title contains no Arabic characters.
     */
    public function test_arabic_sneakers_slang_matches_english_sneakers_product(): void
    {
        $user = User::factory()->create();
        $sneakers = $this->activeProduct('Nike Sneakers');
        $unrelated = $this->activeProduct('Coffee Mug');

        $this->queueIntentAndPickAll([
            'semantic_query' => 'sneakers',
            'arabic_query' => 'حذاء رياضي',
            'keywords' => ['sneakers', 'كوتشي', 'حذاء رياضي'],
            'is_generic_catalog_request' => false,
        ]);
        $this->queueComposerReturn([$sneakers->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/pony/customer/chat', ['message' => 'عايز كوتشي'])
            ->assertOk();

        $ids = collect($response->json('data.products'))->pluck('id')->all();
        $this->assertContains($sneakers->id, $ids);
        $this->assertNotContains($unrelated->id, $ids);
    }

    /**
     * d) "عايز حذاء رياضي" - the MSA / formal phrasing - matches an English
     *    "Sneakers" product through the same expansion mechanism.
     */
    public function test_msa_sports_shoes_phrase_matches_english_sneakers(): void
    {
        $user = User::factory()->create();
        $sneakers = $this->activeProduct('Sneakers');

        $this->queueIntentAndPickAll([
            'semantic_query' => 'sneakers',
            'arabic_query' => 'حذاء رياضي',
            'keywords' => ['sneakers', 'حذاء رياضي', 'sports shoes'],
            'is_generic_catalog_request' => false,
        ]);
        $this->queueComposerReturn([$sneakers->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/pony/customer/chat', ['message' => 'عايز حذاء رياضي'])
            ->assertOk();

        $this->assertContains($sneakers->id, collect($response->json('data.products'))->pluck('id')->all());
    }

    /**
     * e) Inactive / unapproved products are never returned, even for generic
     *    Arabic prompts.
     */
    public function test_inactive_and_unapproved_products_are_excluded(): void
    {
        $user = User::factory()->create();
        $active = $this->activeProduct('Active Item');

        $store = Store::factory()->create(['owner_user_id' => User::factory()->create()->id]);
        $inactive = Product::factory()->create([
            'store_id' => $store->id,
            'title' => 'Inactive Item',
            'status' => ProductStatus::INACTIVE,
            'approval_status' => ProductApprovalStatus::APPROVED,
        ]);
        $pending = Product::factory()->create([
            'store_id' => $store->id,
            'title' => 'Pending Item',
            'status' => ProductStatus::ACTIVE,
            'approval_status' => ProductApprovalStatus::PENDING,
        ]);

        $this->queueIntentAndPickAll([
            'is_generic_catalog_request' => true,
            'keywords' => [],
        ]);
        // Even if Gemini tries to recommend the inactive/pending IDs, the SQL
        // candidate set excludes them so the grounding validator drops them.
        $this->queueComposerReturn([$inactive->id, $pending->id, $active->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/pony/customer/chat', ['message' => 'هل يوجد منتجات'])
            ->assertOk();

        $ids = collect($response->json('data.products'))->pluck('id')->all();
        $this->assertSame([$active->id], $ids);
        $this->assertNotContains($inactive->id, $ids);
        $this->assertNotContains($pending->id, $ids);
    }

    /**
     * f) Hallucinated Gemini IDs are still dropped even when the Arabic
     *    pipeline is in play.
     */
    public function test_hallucinated_product_ids_are_dropped(): void
    {
        $user = User::factory()->create();
        $real = $this->activeProduct('Real Sneakers');

        $this->queueIntentAndPickAll([
            'semantic_query' => 'sneakers',
            'arabic_query' => 'كوتشي',
            'keywords' => ['sneakers', 'كوتشي'],
            'is_generic_catalog_request' => false,
        ]);
        $this->queueComposerReturn(['fake-uuid-1', $real->id, 'fake-uuid-2']);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/pony/customer/chat', ['message' => 'عايز كوتشي'])
            ->assertOk();

        $ids = collect($response->json('data.products'))->pluck('id')->all();
        $this->assertSame([$real->id], $ids);
        $this->assertStringNotContainsString('fake-uuid-1', $response->getContent());
        $this->assertStringNotContainsString('fake-uuid-2', $response->getContent());
    }
}
