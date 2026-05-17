<?php

namespace Tests\Unit\PonyAI;

use App\Domain\PonyAI\Contracts\GeminiClient;
use App\Domain\PonyAI\Repositories\EmbeddingRepository;
use App\Domain\PonyAI\Services\GeminiFakeClient;
use App\Domain\PonyAI\Services\Pipeline\EmbeddingReranker;
use App\Domain\Product\Models\Product;
use App\Domain\Store\Models\Store;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmbeddingRerankerTest extends TestCase
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

    public function test_rerank_orders_by_cosine_similarity(): void
    {
        $a = $this->activeProduct();
        $b = $this->activeProduct();
        $c = $this->activeProduct();

        /** @var EmbeddingRepository $repo */
        $repo = $this->app->make(EmbeddingRepository::class);
        $repo->upsertProductTextEmbedding($a->id, [1.0, 0.0], 1, 'test');
        $repo->upsertProductTextEmbedding($b->id, [0.0, 1.0], 1, 'test');
        $repo->upsertProductTextEmbedding($c->id, [0.7, 0.7], 1, 'test');

        $this->fake()->queueEmbedding([1.0, 0.0]); // query

        $ranked = $this->app->make(EmbeddingReranker::class)->rerank('any text', [$a->id, $b->id, $c->id], 3);

        $this->assertSame([$a->id, $c->id, $b->id], $ranked);
    }

    public function test_candidates_without_embeddings_are_tail_appended(): void
    {
        $a = $this->activeProduct();
        $b = $this->activeProduct(); // no embedding

        $this->app->make(EmbeddingRepository::class)
            ->upsertProductTextEmbedding($a->id, [1.0, 0.0], 1, 'test');

        $this->fake()->queueEmbedding([1.0, 0.0]);

        $ranked = $this->app->make(EmbeddingReranker::class)->rerank('q', [$b->id, $a->id], 5);

        $this->assertSame([$a->id, $b->id], $ranked);
    }

    public function test_gemini_failure_returns_first_n_in_sql_order(): void
    {
        $a = $this->activeProduct();
        $b = $this->activeProduct();

        $throwing = new class implements GeminiClient {
            public function generateText(string $prompt, array $options = []): \App\Domain\PonyAI\DTOs\GeminiResult
            {
                throw new \App\Domain\PonyAI\Exceptions\GeminiException('x');
            }
            public function generateJson(string $prompt, array $options = []): \App\Domain\PonyAI\DTOs\GeminiResult
            {
                throw new \App\Domain\PonyAI\Exceptions\GeminiException('x');
            }
            public function embedText(string $text, array $options = []): array
            {
                throw new \App\Domain\PonyAI\Exceptions\GeminiException('x');
            }
            public function embedImage(string $imageBytes, string $mimeType, array $options = []): array
            {
                throw new \App\Domain\PonyAI\Exceptions\GeminiException('x');
            }
            public function describeImage(string $imageBytes, string $mimeType, string $instruction = '', array $options = []): \App\Domain\PonyAI\DTOs\GeminiResult
            {
                throw new \App\Domain\PonyAI\Exceptions\GeminiException('x');
            }
        };
        $this->app->instance(GeminiClient::class, $throwing);

        $ranked = $this->app->make(EmbeddingReranker::class)->rerank('q', [$a->id, $b->id], 1);

        $this->assertSame([$a->id], $ranked);
    }

    public function test_empty_input_returns_empty(): void
    {
        $this->assertSame([], $this->app->make(EmbeddingReranker::class)->rerank('q', [], 5));
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

        $this->fake()->queueEmbedding([1.0, 0.0]);

        $ranked = $this->app->make(EmbeddingReranker::class)->rerank('q', [$a->id, $b->id, $c->id], 2);

        $this->assertCount(2, $ranked);
        $this->assertSame([$a->id, $b->id], $ranked);
    }
}
