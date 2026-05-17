<?php

namespace Tests\Unit\PonyAI;

use App\Domain\PonyAI\Contracts\GeminiClient;
use App\Domain\PonyAI\Models\PonyProductEmbedding;
use App\Domain\PonyAI\Repositories\EmbeddingRepository;
use App\Domain\PonyAI\Services\GeminiFakeClient;
use App\Domain\PonyAI\Services\ProductEmbeddingService;
use App\Domain\Product\Models\Category;
use App\Domain\Product\Models\Product;
use App\Domain\Product\Models\ProductVariant;
use App\Domain\Store\Models\Store;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductEmbeddingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->setLocale('en');
    }

    private function makeProduct(): Product
    {
        $owner = User::factory()->create();
        $store = Store::factory()->create(['owner_user_id' => $owner->id]);

        $product = Product::factory()->create([
            'store_id' => $store->id,
            'title' => 'Vintage Leather Wallet',
            'short_description' => 'Hand-stitched bifold wallet',
            'description' => 'Made from premium full-grain leather with RFID protection.',
            'currency' => 'EGP',
            'base_price' => 250,
        ]);

        $product->categories()->sync([
            Category::factory()->create(['name_en' => 'Accessories', 'name_ar' => 'إكسسوارات'])->id,
            Category::factory()->create(['name_en' => 'Wallets', 'name_ar' => 'محافظ'])->id,
        ]);

        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'title' => 'Brown / Standard',
            'option_summary' => 'Color: Brown, Size: Standard',
            'is_default' => true,
            'is_active' => true,
            'original_price' => 250,
            'price' => 250,
        ]);

        return $product->fresh(['categories', 'variants.attributes', 'offer']);
    }

    public function test_text_blob_includes_title_description_categories_and_offer(): void
    {
        $product = $this->makeProduct();
        $service = $this->app->make(ProductEmbeddingService::class);

        $blob = $service->buildTextBlob($product);

        $this->assertStringContainsString('Title: Vintage Leather Wallet', $blob);
        $this->assertStringContainsString('Summary: Hand-stitched bifold wallet', $blob);
        $this->assertStringContainsString('Description: Made from premium full-grain leather', $blob);
        $this->assertStringContainsString('Categories: ', $blob);
        $this->assertStringContainsString('Accessories', $blob);
        $this->assertStringContainsString('Wallets', $blob);
        $this->assertStringContainsString('Variants: Brown / Standard', $blob);
        $this->assertStringContainsString('Offer: type=fixed', $blob);
        $this->assertStringContainsString('Price: 250.00 EGP', $blob);
    }

    public function test_embed_stores_vector_with_revision_and_model_version(): void
    {
        config()->set('services.gemini.embed_model', 'text-embedding-test');

        /** @var GeminiFakeClient $fake */
        $fake = $this->app->make(GeminiClient::class);
        $fake->queueEmbedding([0.1, 0.2, 0.3]);

        $product = $this->makeProduct();
        $product->update(['published_revision_no' => 7]);

        $service = $this->app->make(ProductEmbeddingService::class);
        $row = $service->embed($product->fresh());

        $this->assertInstanceOf(PonyProductEmbedding::class, $row);
        $this->assertSame($product->id, $row->product_id);
        $this->assertSame([0.1, 0.2, 0.3], $row->text_embedding);
        $this->assertSame(7, $row->source_revision_no);
        $this->assertSame('text-embedding-test', $row->model_version);
        $this->assertNotNull($row->generated_at);

        $this->assertDatabaseCount('pony_product_embeddings', 1);
    }

    public function test_embed_is_idempotent_on_repeat(): void
    {
        /** @var GeminiFakeClient $fake */
        $fake = $this->app->make(GeminiClient::class);
        $fake->queueEmbedding([0.5, 0.5])->queueEmbedding([0.9, 0.1]);

        $product = $this->makeProduct();
        $service = $this->app->make(ProductEmbeddingService::class);

        $service->embed($product);
        $service->embed($product);

        $this->assertDatabaseCount('pony_product_embeddings', 1);
        $stored = $this->app->make(EmbeddingRepository::class)
            ->findProductEmbeddings([$product->id])
            ->first();
        $this->assertSame([0.9, 0.1], $stored->text_embedding);
    }

    public function test_gemini_call_uses_retrieval_document_task_type(): void
    {
        /** @var GeminiFakeClient $fake */
        $fake = $this->app->make(GeminiClient::class);
        $fake->queueEmbedding([1.0]);

        $service = $this->app->make(ProductEmbeddingService::class);
        $service->embed($this->makeProduct());

        $this->assertSame('embedText', $fake->calls[0]['method']);
        $this->assertSame('RETRIEVAL_DOCUMENT', $fake->calls[0]['options']['task_type']);
    }
}
