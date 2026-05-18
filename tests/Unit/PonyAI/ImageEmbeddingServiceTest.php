<?php

namespace Tests\Unit\PonyAI;

use App\Domain\PonyAI\Contracts\GeminiClient;
use App\Domain\PonyAI\Exceptions\PonyAIException;
use App\Domain\PonyAI\Services\GeminiFakeClient;
use App\Domain\PonyAI\Services\ImageEmbeddingService;
use App\Domain\Product\Models\Product;
use App\Domain\Product\Models\ProductImage;
use App\Domain\Store\Models\Store;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageEmbeddingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    private function makeProductImage(): ProductImage
    {
        $owner = User::factory()->create();
        $store = Store::factory()->create(['owner_user_id' => $owner->id]);
        $product = Product::factory()->create(['store_id' => $store->id]);

        $path = "products/{$product->id}/images/test.jpg";
        Storage::disk('public')->put($path, "\xFF\xD8\xFF\xE0fake-jpeg-bytes-for-testing");

        return ProductImage::create([
            'product_id' => $product->id,
            'image_url' => $path,
            'sort_order' => 0,
            'is_primary' => true,
            'created_at' => now(),
        ]);
    }

    public function test_embed_stores_caption_and_vector(): void
    {
        config()->set('services.gemini.embed_model', 'text-embedding-test');

        /** @var GeminiFakeClient $fake */
        $fake = $this->app->make(GeminiClient::class);
        $fake->queueDescription('red leather wallet bifold')
            ->queueEmbedding([0.4, 0.5, 0.6]);

        $image = $this->makeProductImage();
        $service = $this->app->make(ImageEmbeddingService::class);

        $row = $service->embed($image);

        $this->assertSame($image->id, $row->product_image_id);
        $this->assertSame('red leather wallet bifold', $row->caption);
        $this->assertSame([0.4, 0.5, 0.6], $row->embedding);
        $this->assertSame('text-embedding-test', $row->model_version);
        $this->assertNotNull($row->generated_at);
    }

    public function test_embed_throws_on_empty_caption(): void
    {
        /** @var GeminiFakeClient $fake */
        $fake = $this->app->make(GeminiClient::class);
        $fake->queueDescription('');

        $image = $this->makeProductImage();
        $service = $this->app->make(ImageEmbeddingService::class);

        $this->expectException(PonyAIException::class);
        $this->expectExceptionMessage('empty caption');

        $service->embed($image);
    }

    public function test_embed_throws_when_image_file_missing(): void
    {
        $owner = User::factory()->create();
        $store = Store::factory()->create(['owner_user_id' => $owner->id]);
        $product = Product::factory()->create(['store_id' => $store->id]);
        $image = ProductImage::create([
            'product_id' => $product->id,
            'image_url' => 'products/missing/does-not-exist.jpg',
            'sort_order' => 0,
            'is_primary' => true,
            'created_at' => now(),
        ]);

        $service = $this->app->make(ImageEmbeddingService::class);

        $this->expectException(PonyAIException::class);
        $this->expectExceptionMessage('not present on the public disk');

        $service->embed($image);
    }

    public function test_embed_passes_image_bytes_and_mime_to_describe(): void
    {
        /** @var GeminiFakeClient $fake */
        $fake = $this->app->make(GeminiClient::class);
        $fake->queueDescription('caption')->queueEmbedding([0.1]);

        $service = $this->app->make(ImageEmbeddingService::class);
        $service->embed($this->makeProductImage());

        $describeCall = collect($fake->calls)->firstWhere('method', 'describeImage');
        $embedCall = collect($fake->calls)->firstWhere('method', 'embedText');

        $this->assertNotNull($describeCall);
        $this->assertSame('image/jpeg', $describeCall['mime']);
        $this->assertGreaterThan(0, $describeCall['bytes_length']);

        $this->assertNotNull($embedCall);
        $this->assertSame('caption', $embedCall['prompt']);
        $this->assertSame('RETRIEVAL_DOCUMENT', $embedCall['options']['task_type']);
    }

    private function makeProductImageWithUrl(string $url): ProductImage
    {
        $owner = User::factory()->create();
        $store = Store::factory()->create(['owner_user_id' => $owner->id]);
        $product = Product::factory()->create(['store_id' => $store->id]);

        return ProductImage::create([
            'product_id' => $product->id,
            'image_url' => $url,
            'sort_order' => 0,
            'is_primary' => true,
            'created_at' => now(),
        ]);
    }

    public function test_embed_downloads_external_https_url_and_captions_it(): void
    {
        Http::fake([
            'images.unsplash.com/*' => Http::response(
                "\xFF\xD8\xFF\xE0remote-jpeg-bytes",
                200,
                ['Content-Type' => 'image/jpeg; charset=binary'],
            ),
        ]);

        /** @var GeminiFakeClient $fake */
        $fake = $this->app->make(GeminiClient::class);
        $fake->queueDescription('blue sneaker')->queueEmbedding([0.9, 0.1]);

        $image = $this->makeProductImageWithUrl('https://images.unsplash.com/photo-123?fm=jpg');
        $row = $this->app->make(ImageEmbeddingService::class)->embed($image);

        $this->assertSame($image->id, $row->product_image_id);
        $this->assertSame('blue sneaker', $row->caption);
        $this->assertSame([0.9, 0.1], $row->embedding);

        // Charset suffix is stripped and the bytes the remote returned are what we sent
        // to Gemini Vision - not a local-disk lookup.
        $describeCall = collect($fake->calls)->firstWhere('method', 'describeImage');
        $this->assertNotNull($describeCall);
        $this->assertSame('image/jpeg', $describeCall['mime']);
        $this->assertSame(strlen("\xFF\xD8\xFF\xE0remote-jpeg-bytes"), $describeCall['bytes_length']);

        Http::assertSent(fn ($request) => $request->url() === 'https://images.unsplash.com/photo-123?fm=jpg');
    }

    public function test_embed_throws_when_external_url_returns_404(): void
    {
        Http::fake([
            'cdn.example.com/*' => Http::response('not found', 404),
        ]);

        $image = $this->makeProductImageWithUrl('https://cdn.example.com/gone.jpg');
        $service = $this->app->make(ImageEmbeddingService::class);

        $this->expectException(PonyAIException::class);
        $this->expectExceptionMessage('returned HTTP 404');

        $service->embed($image);
    }

    public function test_embed_throws_on_unsupported_remote_content_type(): void
    {
        Http::fake([
            'cdn.example.com/*' => Http::response(
                '<html>not an image</html>',
                200,
                ['Content-Type' => 'text/html; charset=utf-8'],
            ),
        ]);

        $image = $this->makeProductImageWithUrl('https://cdn.example.com/page.html');
        $service = $this->app->make(ImageEmbeddingService::class);

        $this->expectException(PonyAIException::class);
        $this->expectExceptionMessage('unsupported content-type "text/html"');

        $service->embed($image);
    }

    public function test_embed_throws_when_remote_image_exceeds_size_limit(): void
    {
        // Set a tiny ceiling so we don't have to fake a real 6 MB body.
        config()->set('pony.image_download_max_bytes', 16);

        Http::fake([
            'cdn.example.com/*' => Http::response(
                str_repeat('A', 64), // way over the 16-byte ceiling
                200,
                ['Content-Type' => 'image/png'],
            ),
        ]);

        $image = $this->makeProductImageWithUrl('https://cdn.example.com/huge.png');
        $service = $this->app->make(ImageEmbeddingService::class);

        $this->expectException(PonyAIException::class);
        $this->expectExceptionMessage('exceeds the 16 byte limit');

        $service->embed($image);
    }

    public function test_embed_uses_extension_fallback_when_content_type_missing(): void
    {
        Http::fake([
            'cdn.example.com/*' => Http::response(
                'fake-webp-bytes',
                200,
                [], // no Content-Type header at all
            ),
        ]);

        /** @var GeminiFakeClient $fake */
        $fake = $this->app->make(GeminiClient::class);
        $fake->queueDescription('webp thing')->queueEmbedding([0.2]);

        $image = $this->makeProductImageWithUrl('https://cdn.example.com/photo.webp');
        $this->app->make(ImageEmbeddingService::class)->embed($image);

        $describeCall = collect($fake->calls)->firstWhere('method', 'describeImage');
        $this->assertSame('image/webp', $describeCall['mime']);
    }
}
