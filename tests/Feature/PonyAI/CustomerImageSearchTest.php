<?php

namespace Tests\Feature\PonyAI;

use App\Domain\PonyAI\Contracts\GeminiClient;
use App\Domain\PonyAI\Models\PonyConversation;
use App\Domain\PonyAI\Models\PonyMessage;
use App\Domain\PonyAI\Repositories\EmbeddingRepository;
use App\Domain\PonyAI\Services\GeminiFakeClient;
use App\Domain\Product\Enums\ProductApprovalStatus;
use App\Domain\Product\Enums\ProductStatus;
use App\Domain\Product\Models\Product;
use App\Domain\Store\Models\Store;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CustomerImageSearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->setLocale('en');
        Storage::fake('local');
    }

    private function fake(): GeminiFakeClient
    {
        /** @var GeminiFakeClient $client */
        $client = $this->app->make(GeminiClient::class);

        return $client;
    }

    private function product(string $title = 'Sample Product'): Product
    {
        $store = Store::factory()->create([
            'owner_user_id' => User::factory()->create()->id,
        ]);

        return Product::factory()->create([
            'store_id' => $store->id,
            'title' => $title,
            'short_description' => 'Looking for a great product?',
            'status' => ProductStatus::ACTIVE,
            'approval_status' => ProductApprovalStatus::APPROVED,
        ]);
    }

    private function jpegUpload(): UploadedFile
    {
        // GD is not installed on this host, so UploadedFile::fake()->image() would throw.
        // Use ->create() with a real mime type instead — the bytes don't have to be a
        // valid JPEG; Gemini is faked.
        return UploadedFile::fake()->create('query.jpg', 200, 'image/jpeg');
    }

    public function test_unauthenticated_returns_401(): void
    {
        $this->postJson('/api/v1/pony/customer/image-search', [])
            ->assertStatus(401);
    }

    public function test_authenticated_flow_returns_grounded_products_and_persists_messages(): void
    {
        $user = User::factory()->create();
        $product = $this->product('leather wallet');

        $this->app->make(EmbeddingRepository::class)
            ->upsertProductTextEmbedding($product->id, [1.0, 0.0], 1, 'm');

        // Vision describeImage -> JSON with caption
        $this->fake()->queueDescription(json_encode([
            'caption' => 'a leather wallet',
            'category_guess' => 'wallet',
            'color' => 'brown',
            'attributes' => ['bifold'],
        ]));
        // ImageRanker: embedImage -> describeImage (re-used) + embedText for caption
        $this->fake()->queueDescription('a leather wallet caption');
        $this->fake()->queueEmbedding([1.0, 0.0]); // image embed text (after caption)
        $this->fake()->queueEmbedding([1.0, 0.0]); // caption text query embed
        // AnswerComposer
        $this->fake()->queueJson([
            'message' => 'I found a leather wallet for you.',
            'product_ids' => [$product->id],
            'offer_ids' => [],
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/pony/customer/image-search', [
                'image' => $this->jpegUpload(),
                'message' => 'wallet please',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.message', 'I found a leather wallet for you.')
            ->assertJsonPath('data.products.0.id', $product->id)
            ->assertJsonPath('data.conversation.persona', 'customer');

        $this->assertSame(1, PonyConversation::count());
        $this->assertSame(2, PonyMessage::count());
    }

    public function test_uploaded_image_is_stored_on_private_disk_only(): void
    {
        $user = User::factory()->create();
        $this->product();

        $this->fake()->queueDescription(json_encode(['caption' => 'foo']));
        $this->fake()->queueDescription('foo');
        $this->fake()->queueEmbedding([1.0, 0.0]);
        $this->fake()->queueEmbedding([1.0, 0.0]);
        $this->fake()->queueJson(['message' => 'ok', 'product_ids' => [], 'offer_ids' => []]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/pony/customer/image-search', [
                'image' => $this->jpegUpload(),
            ])->assertOk();

        $files = Storage::disk('local')->files("pony/queries/{$user->id}");
        $this->assertCount(1, $files);

        // The 'public' disk must NOT have received the upload.
        $publicFiles = Storage::disk('public')->allFiles('pony/queries');
        $this->assertSame([], $publicFiles);
    }

    public function test_response_never_echoes_raw_image_path(): void
    {
        $user = User::factory()->create();
        $this->product();

        $this->fake()->queueDescription(json_encode(['caption' => 'foo']));
        $this->fake()->queueDescription('foo');
        $this->fake()->queueEmbedding([1.0, 0.0]);
        $this->fake()->queueEmbedding([1.0, 0.0]);
        $this->fake()->queueJson(['message' => 'ok', 'product_ids' => [], 'offer_ids' => []]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/pony/customer/image-search', [
                'image' => $this->jpegUpload(),
            ])->assertOk();

        $storedFiles = Storage::disk('local')->files("pony/queries/{$user->id}");
        $storedPath = $storedFiles[0] ?? '';

        $body = $response->getContent();
        $this->assertNotSame('', $storedPath);
        $this->assertStringNotContainsString($storedPath, $body);

        // The user message should report only that there was an image, no raw path.
        $userMessageAttachments = $response->json('data.user_message.attachments');
        $this->assertIsArray($userMessageAttachments);
        $this->assertTrue($userMessageAttachments['has_image'] ?? false);
        $this->assertArrayNotHasKey('image', $userMessageAttachments);
    }

    public function test_rejects_non_image_uploads(): void
    {
        $user = User::factory()->create();

        $textFile = UploadedFile::fake()->create('notes.txt', 10, 'text/plain');

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/pony/customer/image-search', [
                'image' => $textFile,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['image']);
    }

    public function test_rejects_oversized_uploads(): void
    {
        $user = User::factory()->create();

        $huge = UploadedFile::fake()->create('huge.jpg', 7000, 'image/jpeg'); // 7 MB

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/pony/customer/image-search', [
                'image' => $huge,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['image']);
    }

    public function test_invented_product_ids_are_dropped(): void
    {
        $user = User::factory()->create();
        $real = $this->product('genuine');

        $this->fake()->queueDescription(json_encode(['caption' => 'genuine']));
        $this->fake()->queueDescription('genuine');
        $this->fake()->queueEmbedding([1.0, 0.0]);
        $this->fake()->queueEmbedding([1.0, 0.0]);
        $this->fake()->queueJson([
            'message' => 'Try these.',
            'product_ids' => ['fake-id-1', $real->id, 'fake-id-2'],
            'offer_ids' => [],
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/pony/customer/image-search', [
                'image' => $this->jpegUpload(),
            ])->assertOk();

        $ids = collect($response->json('data.products'))->pluck('id')->all();
        $this->assertSame([$real->id], $ids);
    }

    public function test_can_continue_existing_conversation_with_image(): void
    {
        $user = User::factory()->create();
        $this->product();

        $this->fake()
            // First call (text chat) creates conversation
            ->queueJson([])
            ->queueEmbedding([1.0])
            ->queueJson(['message' => 'first', 'product_ids' => [], 'offer_ids' => []])
            // Second call (image search)
            ->queueDescription(json_encode(['caption' => 'item']))
            ->queueDescription('item')
            ->queueEmbedding([1.0, 0.0])
            ->queueEmbedding([1.0, 0.0])
            ->queueJson(['message' => 'second', 'product_ids' => [], 'offer_ids' => []]);

        $conversationId = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/pony/customer/chat', ['message' => 'hi'])
            ->json('data.conversation.id');

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/pony/customer/image-search', [
                'image' => $this->jpegUpload(),
                'conversation_id' => $conversationId,
            ])
            ->assertOk()
            ->assertJsonPath('data.conversation.id', $conversationId);

        $this->assertSame(1, PonyConversation::count());
        $this->assertSame(4, PonyMessage::count());
    }

    public function test_referencing_someone_elses_conversation_returns_404(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();

        $owned = $this->app->make(\App\Domain\PonyAI\Repositories\ConversationRepository::class)
            ->startCustomer($owner);

        $this->actingAs($intruder, 'sanctum')
            ->postJson('/api/v1/pony/customer/image-search', [
                'image' => $this->jpegUpload(),
                'conversation_id' => $owned->id,
            ])
            ->assertStatus(404);
    }
}
