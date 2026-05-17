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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class SignedImageUrlTest extends TestCase
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

    private function product(): Product
    {
        $store = Store::factory()->create([
            'owner_user_id' => User::factory()->create()->id,
        ]);

        return Product::factory()->create([
            'store_id' => $store->id,
            'title' => 'thing',
            'status' => ProductStatus::ACTIVE,
            'approval_status' => ProductApprovalStatus::APPROVED,
        ]);
    }

    private function uploadAndGetImageUrl(User $user): array
    {
        $this->product();

        $this->fake()
            ->queueDescription(json_encode(['caption' => 'cap']))
            ->queueDescription('cap')
            ->queueEmbedding([1.0, 0.0])
            ->queueEmbedding([1.0, 0.0])
            ->queueJson(['message' => 'ok', 'product_ids' => [], 'offer_ids' => []]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/pony/customer/image-search', [
                'image' => UploadedFile::fake()->create('q.jpg', 100, 'image/jpeg'),
            ])
            ->assertOk();

        $imageUrl = $response->json('data.user_message.attachments.image_url');
        $this->assertNotEmpty($imageUrl);

        return [
            $imageUrl,
            $response->json('data.user_message.id'),
        ];
    }

    public function test_message_resource_surfaces_a_signed_image_url(): void
    {
        $user = User::factory()->create();
        [$imageUrl, $messageId] = $this->uploadAndGetImageUrl($user);

        $this->assertStringContainsString("/api/v1/pony/customer/images/{$messageId}", $imageUrl);
        $this->assertStringContainsString('signature=', $imageUrl);
        $this->assertStringContainsString('expires=', $imageUrl);
    }

    public function test_signed_url_returns_the_stored_image(): void
    {
        $user = User::factory()->create();
        [$imageUrl] = $this->uploadAndGetImageUrl($user);

        $relativeUrl = $this->stripHost($imageUrl);

        $response = $this->get($relativeUrl);

        $response->assertOk();
        $this->assertSame('image/jpeg', $response->headers->get('Content-Type'));

        // Storage::response() returns a BinaryFileResponse - the test HTTP client doesn't
        // materialise its body, so we verify the upload landed on disk in the first place.
        $storedFiles = Storage::disk('local')->allFiles('pony/queries');
        $this->assertCount(1, $storedFiles);
        $this->assertTrue(Storage::disk('local')->exists($storedFiles[0]));
    }

    public function test_tampering_with_the_signature_returns_403(): void
    {
        $user = User::factory()->create();
        [$imageUrl] = $this->uploadAndGetImageUrl($user);

        $tampered = str_replace('signature=', 'signature=bad', $this->stripHost($imageUrl));

        $this->get($tampered)->assertStatus(403);
    }

    public function test_expired_url_returns_403(): void
    {
        config()->set('pony.image_url_ttl_minutes', 1);

        $user = User::factory()->create();
        [$imageUrl] = $this->uploadAndGetImageUrl($user);

        $relativeUrl = $this->stripHost($imageUrl);

        Carbon::setTestNow(Carbon::now()->addMinutes(5));
        $this->get($relativeUrl)->assertStatus(403);
        Carbon::setTestNow(null);
    }

    public function test_purged_file_returns_404_even_with_valid_signature(): void
    {
        $user = User::factory()->create();
        [$imageUrl, $messageId] = $this->uploadAndGetImageUrl($user);

        // Wipe the file the upload created.
        foreach (Storage::disk('local')->allFiles('pony/queries') as $path) {
            Storage::disk('local')->delete($path);
        }

        $this->get($this->stripHost($imageUrl))->assertStatus(404);
    }

    public function test_signed_route_can_be_generated_directly_via_url_helper(): void
    {
        $user = User::factory()->create();
        [$imageUrl, $messageId] = $this->uploadAndGetImageUrl($user);

        $regenerated = URL::temporarySignedRoute(
            'pony.customer.images.show',
            now()->addMinutes(30),
            ['message' => $messageId],
        );

        $this->get($this->stripHost($regenerated))->assertOk();
    }

    private function stripHost(string $url): string
    {
        $parsed = parse_url($url);

        return ($parsed['path'] ?? '/').(isset($parsed['query']) ? '?'.$parsed['query'] : '');
    }
}
