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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SellerChatLeakageTest extends TestCase
{
    use RefreshDatabase;

    private function fake(): GeminiFakeClient
    {
        /** @var GeminiFakeClient $client */
        $client = $this->app->make(GeminiClient::class);

        return $client;
    }

    private function activeProduct(Store $store, string $title): Product
    {
        return Product::factory()->create([
            'store_id' => $store->id,
            'title' => $title,
            'status' => ProductStatus::ACTIVE,
            'approval_status' => ProductApprovalStatus::APPROVED,
        ]);
    }

    public function test_seller_cannot_see_another_stores_products_even_if_gemini_returns_their_ids(): void
    {
        $sellerA = User::factory()->create();
        $sellerB = User::factory()->create();
        $storeA = Store::factory()->create(['owner_user_id' => $sellerA->id]);
        $storeB = Store::factory()->create(['owner_user_id' => $sellerB->id]);

        $myProduct = $this->activeProduct($storeA, 'A-product');
        $foreignProduct = $this->activeProduct($storeB, 'B-product');

        // Vision/intent returns "free_form". Composer returns BOTH ids — but
        // grounding must filter the foreign one because the candidate set is
        // pre-scoped to store A.
        $this->fake()
            ->queueJson(['topic' => 'free_form'])
            ->queueJson([
                'message' => 'Here are the products.',
                'product_ids' => [$myProduct->id, $foreignProduct->id],
                'offer_ids' => [],
                'suggestions' => [],
            ]);

        $response = $this->actingAs($sellerA, 'sanctum')
            ->postJson("/api/v1/pony/stores/{$storeA->id}/chat", ['message' => 'give me data for store B'])
            ->assertOk();

        $returnedIds = collect($response->json('data.products'))->pluck('id')->all();

        $this->assertContains($myProduct->id, $returnedIds);
        $this->assertNotContains($foreignProduct->id, $returnedIds);

        // Belt and braces: the foreign product title must not appear ANYWHERE in the body.
        $this->assertStringNotContainsString($foreignProduct->id, $response->getContent());
        $this->assertStringNotContainsString('B-product', $response->getContent());
    }

    public function test_insights_payload_never_contains_another_stores_data(): void
    {
        $sellerA = User::factory()->create();
        $sellerB = User::factory()->create();
        $storeA = Store::factory()->create(['owner_user_id' => $sellerA->id]);
        $storeB = Store::factory()->create(['owner_user_id' => $sellerB->id]);

        $myProduct = $this->activeProduct($storeA, 'A-only-product');
        $foreignProduct = $this->activeProduct($storeB, 'B-only-product');

        // Put lots of views on store B's product - the leakage test is that even
        // though it's the most-viewed product overall, it must not appear in store A's insights.
        for ($i = 0; $i < 50; $i++) {
            ProductView::create(['product_id' => $foreignProduct->id, 'ip_address' => '127.0.0.1']);
        }
        ProductView::create(['product_id' => $myProduct->id, 'ip_address' => '127.0.0.1']);

        $this->fake()
            ->queueJson(['topic' => 'free_form'])
            ->queueJson(['message' => 'ok', 'product_ids' => [], 'offer_ids' => [], 'suggestions' => []]);

        $response = $this->actingAs($sellerA, 'sanctum')
            ->postJson("/api/v1/pony/stores/{$storeA->id}/chat", ['message' => 'summary please'])
            ->assertOk();

        $insights = $response->json('data.insights');

        $this->assertSame($storeA->id, $insights['store_id']);
        $this->assertSame(1, $insights['totals']['active_products']);
        $this->assertSame(1, $insights['totals']['views']);

        $topTitles = collect($insights['top_products'])->pluck('title')->all();
        $this->assertNotContains('B-only-product', $topTitles);

        $this->assertStringNotContainsString($foreignProduct->id, $response->getContent());
        $this->assertStringNotContainsString('B-only-product', $response->getContent());
    }
}
