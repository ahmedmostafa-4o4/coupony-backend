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

class CustomerChatFastPathTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->setLocale('en');

        // The phpunit env disables fast_mode by default so the older composer-output
        // tests keep their exact-message assertions intact. The fast-path tests are
        // explicitly about that path, so we flip the knob on for this class only.
        config()->set('pony.fast_mode', true);
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

    /**
     * a) Generic Arabic prompt returns products without touching Gemini at all.
     */
    public function test_generic_arabic_prompt_skips_gemini_entirely(): void
    {
        $user = User::factory()->create();
        $product = $this->activeProduct('Mug');

        // Note: no Gemini responses queued. If the fast path is broken and the
        // strategy tries to call Gemini, the fake will fall back to deterministic
        // synthetic responses - the calls[] log lets us assert what actually fired.
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/pony/customer/chat', ['message' => 'هل يوجد منتجات'])
            ->assertOk();

        $this->assertSame([], $this->fake()->calls, 'Generic catalog path must not call Gemini.');
        $this->assertContains($product->id, collect($response->json('data.products'))->pluck('id')->all());
    }

    /**
     * b) Different generic Arabic phrasing surfaces popular active+approved products.
     */
    public function test_show_products_arabic_returns_popular_catalog(): void
    {
        $user = User::factory()->create();
        $a = $this->activeProduct('Sneakers');
        $b = $this->activeProduct('Wallet');
        $c = $this->activeProduct('Watch');

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/pony/customer/chat', ['message' => 'اعرض المنتجات'])
            ->assertOk();

        $this->assertSame([], $this->fake()->calls);

        $ids = collect($response->json('data.products'))->pluck('id')->all();
        $this->assertEqualsCanonicalizing([$a->id, $b->id, $c->id], $ids);
    }

    /**
     * c) Inactive / unapproved products are never returned, even via the fast path.
     */
    public function test_inactive_and_unapproved_products_are_excluded_from_fast_path(): void
    {
        $user = User::factory()->create();
        $approved = $this->activeProduct('Approved Item');

        $store = Store::factory()->create(['owner_user_id' => User::factory()->create()->id]);
        Product::factory()->create([
            'store_id' => $store->id,
            'title' => 'Inactive Item',
            'status' => ProductStatus::INACTIVE,
            'approval_status' => ProductApprovalStatus::APPROVED,
        ]);
        Product::factory()->create([
            'store_id' => $store->id,
            'title' => 'Pending Item',
            'status' => ProductStatus::ACTIVE,
            'approval_status' => ProductApprovalStatus::PENDING,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/pony/customer/chat', ['message' => 'وريني المنتجات'])
            ->assertOk();

        $this->assertSame([], $this->fake()->calls);
        $ids = collect($response->json('data.products'))->pluck('id')->all();
        $this->assertSame([$approved->id], $ids);
    }

    /**
     * d) The assistant message metadata records fast_path=generic_catalog and skipped_gemini=true.
     */
    public function test_fast_path_metadata_is_surfaced_on_assistant_message(): void
    {
        $user = User::factory()->create();
        $this->activeProduct('Anything');

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/pony/customer/chat', ['message' => 'ايه المتاح'])
            ->assertOk();

        $metadata = $response->json('data.assistant_message.metadata');
        $this->assertIsArray($metadata);
        $this->assertSame('generic_catalog', $metadata['fast_path'] ?? null);
        $this->assertTrue($metadata['skipped_gemini'] ?? false);
    }

    /**
     * e) A specific (non-generic) Arabic prompt still runs through the regular pipeline.
     *    Gemini is called for intent extraction; fast_mode then skips AnswerComposer
     *    but the reply is still grounded in real products.
     */
    public function test_specific_arabic_prompt_runs_through_normal_pipeline(): void
    {
        $user = User::factory()->create();
        $product = $this->activeProduct('Nike Sneakers');

        // Queue intent only - fast_mode (enabled by default) will skip AnswerComposer.
        $this->fake()->queueJson([
            'semantic_query' => 'sneakers',
            'arabic_query' => 'كوتشي',
            'keywords' => ['sneakers', 'كوتشي'],
            'is_generic_catalog_request' => false,
        ]);
        $this->fake()->queueEmbedding([1.0, 0.0]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/pony/customer/chat', ['message' => 'عايز كوتشي'])
            ->assertOk();

        // Intent was extracted and the embedding query was issued - the fast generic
        // path was NOT taken.
        $methodsCalled = collect($this->fake()->calls)->pluck('method')->all();
        $this->assertContains('generateJson', $methodsCalled);
        $this->assertContains('embedText', $methodsCalled);

        $ids = collect($response->json('data.products'))->pluck('id')->all();
        $this->assertContains($product->id, $ids);

        // Fast mode flags - this turn went through gemini for intent but skipped composer.
        $metadata = $response->json('data.assistant_message.metadata');
        $this->assertNotSame('generic_catalog', $metadata['fast_path'] ?? null);
        $this->assertFalse($metadata['skipped_gemini'] ?? true);
    }
}
