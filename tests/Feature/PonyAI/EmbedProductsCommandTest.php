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

class EmbedProductsCommandTest extends TestCase
{
    use RefreshDatabase;

    private function activeProduct(?Store $store = null): Product
    {
        $store ??= Store::factory()->create([
            'owner_user_id' => User::factory()->create()->id,
        ]);

        return Product::factory()->create([
            'store_id' => $store->id,
            'status' => ProductStatus::ACTIVE,
            'approval_status' => ProductApprovalStatus::APPROVED,
        ]);
    }

    public function test_command_embeds_all_eligible_products(): void
    {
        /** @var GeminiFakeClient $fake */
        $fake = $this->app->make(GeminiClient::class);
        $fake->queueEmbedding([0.1, 0.2])
            ->queueEmbedding([0.3, 0.4])
            ->queueEmbedding([0.5, 0.6]);

        $this->activeProduct();
        $this->activeProduct();
        $this->activeProduct();

        $this->artisan('pony:embed-products')->assertSuccessful();

        $this->assertDatabaseCount('pony_product_embeddings', 3);
    }

    public function test_command_skips_already_embedded_products_by_default(): void
    {
        /** @var GeminiFakeClient $fake */
        $fake = $this->app->make(GeminiClient::class);

        $existing = $this->activeProduct();
        $newcomer = $this->activeProduct();

        $fake->queueEmbedding([0.9, 0.9]); // for the existing one first
        $this->artisan('pony:embed-products', ['--store' => $existing->store_id])->assertSuccessful();
        $this->assertDatabaseCount('pony_product_embeddings', 1);

        $fake->queueEmbedding([0.1, 0.1]); // only one new call expected for newcomer
        $this->artisan('pony:embed-products')->assertSuccessful();

        $this->assertDatabaseCount('pony_product_embeddings', 2);
        $this->assertDatabaseHas('pony_product_embeddings', ['product_id' => $newcomer->id]);
    }

    public function test_force_flag_re_embeds_existing(): void
    {
        /** @var GeminiFakeClient $fake */
        $fake = $this->app->make(GeminiClient::class);
        $product = $this->activeProduct();

        $fake->queueEmbedding([0.1, 0.1]);
        $this->artisan('pony:embed-products')->assertSuccessful();

        $fake->queueEmbedding([0.7, 0.7]);
        $this->artisan('pony:embed-products', ['--force' => true])->assertSuccessful();

        $row = \App\Domain\PonyAI\Models\PonyProductEmbedding::find($product->id);
        $this->assertSame([0.7, 0.7], $row->text_embedding);
        $this->assertDatabaseCount('pony_product_embeddings', 1);
    }

    public function test_store_filter_limits_scope(): void
    {
        /** @var GeminiFakeClient $fake */
        $fake = $this->app->make(GeminiClient::class);

        $storeA = Store::factory()->create(['owner_user_id' => User::factory()->create()->id]);
        $storeB = Store::factory()->create(['owner_user_id' => User::factory()->create()->id]);

        $this->activeProduct($storeA);
        $this->activeProduct($storeB);

        $fake->queueEmbedding([0.1]);
        $this->artisan('pony:embed-products', ['--store' => $storeA->id])->assertSuccessful();

        $this->assertDatabaseCount('pony_product_embeddings', 1);
    }

    public function test_command_ignores_inactive_or_unapproved_products(): void
    {
        /** @var GeminiFakeClient $fake */
        $fake = $this->app->make(GeminiClient::class);

        Product::factory()->create([
            'store_id' => Store::factory()->create(['owner_user_id' => User::factory()->create()->id])->id,
            'status' => ProductStatus::INACTIVE,
            'approval_status' => ProductApprovalStatus::APPROVED,
        ]);

        Product::factory()->create([
            'store_id' => Store::factory()->create(['owner_user_id' => User::factory()->create()->id])->id,
            'status' => ProductStatus::ACTIVE,
            'approval_status' => ProductApprovalStatus::PENDING,
        ]);

        $this->artisan('pony:embed-products')
            ->expectsOutputToContain('No products to embed.')
            ->assertSuccessful();

        $this->assertDatabaseCount('pony_product_embeddings', 0);
        $this->assertSame(0, count($fake->calls));
    }
}
