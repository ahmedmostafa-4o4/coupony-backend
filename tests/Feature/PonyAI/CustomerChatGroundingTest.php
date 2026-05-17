<?php

namespace Tests\Feature\PonyAI;

use App\Domain\PonyAI\Contracts\GeminiClient;
use App\Domain\PonyAI\Repositories\EmbeddingRepository;
use App\Domain\PonyAI\Services\GeminiFakeClient;
use App\Domain\Product\Enums\ProductApprovalStatus;
use App\Domain\Product\Enums\ProductStatus;
use App\Domain\Product\Models\Product;
use App\Domain\Store\Models\Store;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerChatGroundingTest extends TestCase
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

    private function realProduct(string $title): Product
    {
        $store = Store::factory()->create([
            'owner_user_id' => User::factory()->create()->id,
        ]);

        return Product::factory()->create([
            'store_id' => $store->id,
            'title' => $title,
            'status' => ProductStatus::ACTIVE,
            'approval_status' => ProductApprovalStatus::APPROVED,
        ]);
    }

    public function test_response_drops_product_ids_that_are_not_in_the_candidate_set(): void
    {
        $user = User::factory()->create();
        $real = $this->realProduct('genuine wallet');

        $this->app->make(EmbeddingRepository::class)
            ->upsertProductTextEmbedding($real->id, [1.0, 0.0], 1, 'm');

        $this->fake()
            ->queueJson([])                                     // intent
            ->queueEmbedding([1.0, 0.0])                        // query embedding
            ->queueJson([                                       // composer: invented IDs + 1 real
                'message' => 'Try these.',
                'product_ids' => [
                    'totally-fake-uuid-1',
                    $real->id,
                    'another-hallucination',
                ],
                'offer_ids' => [],
            ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/pony/customer/chat', ['message' => 'wallet please'])
            ->assertOk();

        $returnedIds = collect($response->json('data.products'))->pluck('id')->all();

        $this->assertSame([$real->id], $returnedIds);

        foreach ($returnedIds as $id) {
            $product = Product::find($id);
            $this->assertNotNull($product, "Returned product {$id} should exist in DB");
            $this->assertSame(ProductStatus::ACTIVE, $product->status);
            $this->assertSame(ProductApprovalStatus::APPROVED, $product->approval_status);
        }
    }

    public function test_response_never_includes_inactive_or_unapproved_products(): void
    {
        $user = User::factory()->create();

        $real = $this->realProduct('approved item');
        $store = Store::factory()->create(['owner_user_id' => User::factory()->create()->id]);
        $inactive = Product::factory()->create([
            'store_id' => $store->id,
            'title' => 'inactive item',
            'status' => ProductStatus::INACTIVE,
            'approval_status' => ProductApprovalStatus::APPROVED,
        ]);
        $pending = Product::factory()->create([
            'store_id' => $store->id,
            'title' => 'pending item',
            'status' => ProductStatus::ACTIVE,
            'approval_status' => ProductApprovalStatus::PENDING,
        ]);

        $this->fake()
            ->queueJson([])
            ->queueEmbedding([1.0])
            ->queueJson([
                'message' => 'Picks.',
                'product_ids' => [$inactive->id, $pending->id, $real->id],
                'offer_ids' => [],
            ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/pony/customer/chat', ['message' => 'item'])
            ->assertOk();

        $returnedIds = collect($response->json('data.products'))->pluck('id')->all();

        $this->assertSame([$real->id], $returnedIds);
        $this->assertNotContains($inactive->id, $returnedIds);
        $this->assertNotContains($pending->id, $returnedIds);
    }
}
