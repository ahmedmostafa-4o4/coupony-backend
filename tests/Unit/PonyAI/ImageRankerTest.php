<?php

namespace Tests\Unit\PonyAI;

use App\Domain\PonyAI\Contracts\GeminiClient;
use App\Domain\PonyAI\Repositories\EmbeddingRepository;
use App\Domain\PonyAI\Services\GeminiFakeClient;
use App\Domain\PonyAI\Services\Pipeline\ImageRanker;
use App\Domain\Product\Models\Product;
use App\Domain\Product\Models\ProductImage;
use App\Domain\Store\Models\Store;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImageRankerTest extends TestCase
{
    use RefreshDatabase;

    private function fake(): GeminiFakeClient
    {
        /** @var GeminiFakeClient $client */
        $client = $this->app->make(GeminiClient::class);

        return $client;
    }

    private function activeProduct(): Product
    {
        $store = Store::factory()->create([
            'owner_user_id' => User::factory()->create()->id,
        ]);

        return Product::factory()->create(['store_id' => $store->id]);
    }

    private function attachImage(Product $product): ProductImage
    {
        return ProductImage::create([
            'product_id' => $product->id,
            'image_url' => "products/{$product->id}/images/fake.jpg",
            'sort_order' => 0,
            'is_primary' => true,
            'created_at' => now(),
        ]);
    }

    public function test_rerank_orders_by_combined_image_and_text_cosine(): void
    {
        config()->set('services.gemini.image_rank_alpha', 0.5);

        $a = $this->activeProduct(); // strong image match, weak text match
        $b = $this->activeProduct(); // weak image, strong text

        $imgA = $this->attachImage($a);
        $imgB = $this->attachImage($b);

        $repo = $this->app->make(EmbeddingRepository::class);
        $repo->upsertImageEmbedding($imgA->id, [1.0, 0.0], 'caption-a', 'm');
        $repo->upsertImageEmbedding($imgB->id, [0.0, 1.0], 'caption-b', 'm');
        $repo->upsertProductTextEmbedding($a->id, [0.0, 1.0], 1, 'm');
        $repo->upsertProductTextEmbedding($b->id, [1.0, 0.0], 1, 'm');

        // Both image query and text query line up with A's image and B's text equally,
        // but the blended score should rank A first because we queue image vector matching
        // strongly to A and text vector matching strongly to A as well.
        $this->fake()->queueDescription('caption-a-like'); // describeImage (called by embedImage)
        $this->fake()->queueEmbedding([1.0, 0.0]);          // embedText for caption -> image embed flow
        $this->fake()->queueEmbedding([0.0, 1.0]);          // safeEmbedText (caption-vector for text rerank)

        $ranker = $this->app->make(ImageRanker::class);
        $ranked = $ranker->rerank('bytes', 'image/jpeg', 'caption-a-like', [$a->id, $b->id], 2);

        $this->assertSame([$a->id, $b->id], $ranked);
    }

    public function test_candidates_without_embeddings_are_tail_appended(): void
    {
        $a = $this->activeProduct();
        $b = $this->activeProduct(); // no embeddings at all
        $imgA = $this->attachImage($a);

        $repo = $this->app->make(EmbeddingRepository::class);
        $repo->upsertImageEmbedding($imgA->id, [1.0, 0.0], 'cap', 'm');
        $repo->upsertProductTextEmbedding($a->id, [1.0, 0.0], 1, 'm');

        $this->fake()->queueDescription('cap');
        $this->fake()->queueEmbedding([1.0, 0.0]); // image-bytes -> caption -> embed
        $this->fake()->queueEmbedding([1.0, 0.0]); // caption text query embed

        $ranker = $this->app->make(ImageRanker::class);
        $ranked = $ranker->rerank('bytes', 'image/jpeg', 'cap', [$b->id, $a->id], 5);

        $this->assertSame([$a->id, $b->id], $ranked);
    }

    public function test_gemini_failure_returns_sql_order_subset(): void
    {
        $a = $this->activeProduct();
        $b = $this->activeProduct();

        $throwing = new class implements GeminiClient
        {
            public function generateText(string $p, array $o = []): \App\Domain\PonyAI\DTOs\GeminiResult
            {
                throw new \App\Domain\PonyAI\Exceptions\GeminiException('x');
            }

            public function generateJson(string $p, array $o = []): \App\Domain\PonyAI\DTOs\GeminiResult
            {
                throw new \App\Domain\PonyAI\Exceptions\GeminiException('x');
            }

            public function embedText(string $t, array $o = []): array
            {
                throw new \App\Domain\PonyAI\Exceptions\GeminiException('x');
            }

            public function embedImage(string $b, string $m, array $o = []): array
            {
                throw new \App\Domain\PonyAI\Exceptions\GeminiException('x');
            }

            public function describeImage(string $b, string $m, string $i = '', array $o = []): \App\Domain\PonyAI\DTOs\GeminiResult
            {
                throw new \App\Domain\PonyAI\Exceptions\GeminiException('x');
            }
        };
        $this->app->instance(GeminiClient::class, $throwing);

        $ranked = $this->app->make(ImageRanker::class)->rerank('bytes', 'image/jpeg', 'cap', [$a->id, $b->id], 1);

        $this->assertSame([$a->id], $ranked);
    }

    public function test_respects_top_k(): void
    {
        $a = $this->activeProduct();
        $b = $this->activeProduct();
        $c = $this->activeProduct();

        $repo = $this->app->make(EmbeddingRepository::class);
        $repo->upsertProductTextEmbedding($a->id, [1.0, 0.0], 1, 'm');
        $repo->upsertProductTextEmbedding($b->id, [0.9, 0.1], 1, 'm');
        $repo->upsertProductTextEmbedding($c->id, [-1.0, 0.0], 1, 'm');

        // No image embeddings on candidates → only text dimension contributes.
        $this->fake()->queueDescription('caption');
        $this->fake()->queueEmbedding([1.0, 0.0]); // image-bytes embed
        $this->fake()->queueEmbedding([1.0, 0.0]); // caption text embed

        $ranked = $this->app->make(ImageRanker::class)->rerank('bytes', 'image/jpeg', 'caption', [$a->id, $b->id, $c->id], 2);

        $this->assertCount(2, $ranked);
        $this->assertSame([$a->id, $b->id], $ranked);
    }
}
