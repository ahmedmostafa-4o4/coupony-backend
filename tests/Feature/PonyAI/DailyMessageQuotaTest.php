<?php

namespace Tests\Feature\PonyAI;

use App\Domain\PonyAI\Contracts\GeminiClient;
use App\Domain\PonyAI\DTOs\GeminiResult;
use App\Domain\PonyAI\Exceptions\PonyAIException;
use App\Domain\PonyAI\Models\AiMessageUsage;
use App\Domain\PonyAI\Repositories\ConversationRepository;
use App\Domain\PonyAI\Services\GeminiFakeClient;
use App\Domain\Store\Models\Store;
use App\Domain\Store\Models\StoreEmployee;
use App\Domain\Subscription\Enums\SubscriptionStatus;
use App\Domain\Subscription\Models\Subscription;
use App\Domain\Subscription\Models\SubscriptionPlan;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DailyMessageQuotaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->setLocale('en');
        $this->app->detectEnvironment(fn () => 'production');
        $this->app->forgetInstance(GeminiClient::class);
        $this->app->instance(GeminiClient::class, new GeminiFakeClient);
        config()->set('pony.quotas.customer_daily_limit', 1);
        Storage::fake('local');
    }

    public function test_customer_text_and_image_share_daily_quota(): void
    {
        $user = User::factory()->create();
        $this->queueCustomerChat('first');

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/pony/customer/chat', ['message' => 'hi'])
            ->assertOk()
            ->assertJsonPath('data.quota.limit', 1)
            ->assertJsonPath('data.quota.used', 1)
            ->assertJsonPath('data.quota.remaining', 0);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/pony/customer/image-search', [
                'image' => $this->jpegUpload(),
                'message' => 'wallet please',
            ])
            ->assertStatus(429)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error_code', 'AI_DAILY_LIMIT_REACHED')
            ->assertJsonPath('quota.limit', 1)
            ->assertJsonPath('quota.used', 1)
            ->assertJsonPath('quota.remaining', 0);
    }

    public function test_customer_quota_is_unlimited_outside_production(): void
    {
        $this->app->detectEnvironment(fn () => 'local');

        $user = User::factory()->create();
        $this->queueCustomerChat('first');
        $this->queueCustomerChat('second');

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/pony/customer/chat', ['message' => 'hi'])
            ->assertOk()
            ->assertJsonPath('data.quota.limit', null)
            ->assertJsonPath('data.quota.remaining', null);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/pony/customer/chat', ['message' => 'again'])
            ->assertOk()
            ->assertJsonPath('data.quota.limit', null);

        $this->assertSame(0, AiMessageUsage::query()->where('subject_type', 'customer')->count());
    }

    public function test_validation_and_missing_conversation_do_not_consume_customer_quota(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $ownedConversation = $this->app->make(ConversationRepository::class)->startCustomer($owner);

        $this->actingAs($intruder, 'sanctum')
            ->postJson('/api/v1/pony/customer/image-search', [
                'image' => UploadedFile::fake()->create('notes.txt', 10, 'text/plain'),
            ])
            ->assertStatus(422);

        $this->actingAs($intruder, 'sanctum')
            ->postJson('/api/v1/pony/customer/chat', [
                'message' => 'sneaky',
                'conversation_id' => $ownedConversation->id,
            ])
            ->assertStatus(404);

        $this->queueCustomerChat('available after failed requests');

        $this->actingAs($intruder, 'sanctum')
            ->postJson('/api/v1/pony/customer/chat', ['message' => 'hi'])
            ->assertOk()
            ->assertJsonPath('data.quota.used', 1)
            ->assertJsonPath('data.quota.remaining', 0);
    }

    public function test_ai_exception_releases_customer_quota(): void
    {
        $user = User::factory()->create();

        $this->app->instance(GeminiClient::class, new class implements GeminiClient
        {
            private int $jsonCalls = 0;

            public function generateText(string $prompt, array $options = []): GeminiResult
            {
                return new GeminiResult(text: 'ok');
            }

            public function generateJson(string $prompt, array $options = []): GeminiResult
            {
                $this->jsonCalls++;

                if ($this->jsonCalls === 1) {
                    throw new PonyAIException('intent failed');
                }

                $payload = $this->jsonCalls === 2
                    ? []
                    : ['message' => 'recovered', 'product_ids' => [], 'offer_ids' => []];

                return new GeminiResult(text: json_encode($payload) ?: '{}');
            }

            public function embedText(string $text, array $options = []): array
            {
                return [1.0];
            }

            public function embedImage(string $imageBytes, string $mimeType, array $options = []): array
            {
                return [1.0];
            }

            public function describeImage(string $imageBytes, string $mimeType, string $instruction = '', array $options = []): GeminiResult
            {
                return new GeminiResult(text: 'caption');
            }
        });

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/pony/customer/chat', ['message' => 'first'])
            ->assertStatus(422);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/pony/customer/chat', ['message' => 'second'])
            ->assertOk()
            ->assertJsonPath('data.quota.used', 1)
            ->assertJsonPath('data.quota.remaining', 0);
    }

    public function test_seller_quota_is_shared_by_store_users(): void
    {
        $owner = User::factory()->create();
        $employee = User::factory()->create();
        $store = $this->subscribedStore($owner, 1);

        StoreEmployee::create([
            'store_id' => $store->id,
            'user_id' => $employee->id,
            'role' => 'cashier',
            'permissions' => [],
        ]);

        $this->queueSellerChat('owner turn');

        $this->actingAs($owner, 'sanctum')
            ->postJson("/api/v1/pony/stores/{$store->id}/chat", ['message' => 'how are sales?'])
            ->assertOk()
            ->assertJsonPath('data.quota.limit', 1)
            ->assertJsonPath('data.quota.remaining', 0);

        $this->actingAs($employee, 'sanctum')
            ->postJson("/api/v1/pony/stores/{$store->id}/chat", ['message' => 'what next?'])
            ->assertStatus(429)
            ->assertJsonPath('error_code', 'AI_DAILY_LIMIT_REACHED')
            ->assertJsonPath('quota.remaining', 0);
    }

    public function test_seller_quota_is_isolated_by_store(): void
    {
        $owner = User::factory()->create();
        $storeA = $this->subscribedStore($owner, 1);
        $storeB = $this->subscribedStore($owner, 1);

        $this->queueSellerChat('store a');
        $this->queueSellerChat('store b');

        $this->actingAs($owner, 'sanctum')
            ->postJson("/api/v1/pony/stores/{$storeA->id}/chat", ['message' => 'a'])
            ->assertOk()
            ->assertJsonPath('data.quota.remaining', 0);

        $this->actingAs($owner, 'sanctum')
            ->postJson("/api/v1/pony/stores/{$storeB->id}/chat", ['message' => 'b'])
            ->assertOk()
            ->assertJsonPath('data.quota.remaining', 0);
    }

    private function fake(): GeminiFakeClient
    {
        /** @var GeminiFakeClient $client */
        $client = $this->app->make(GeminiClient::class);

        return $client;
    }

    private function queueCustomerChat(string $message): void
    {
        $this->fake()
            ->queueJson([])
            ->queueEmbedding([1.0])
            ->queueJson(['message' => $message, 'product_ids' => [], 'offer_ids' => []]);
    }

    private function queueSellerChat(string $message): void
    {
        $this->fake()
            ->queueJson(['topic' => 'free_form'])
            ->queueJson([
                'message' => $message,
                'product_ids' => [],
                'offer_ids' => [],
                'suggestions' => [],
            ]);
    }

    private function jpegUpload(): UploadedFile
    {
        return UploadedFile::fake()->create('query.jpg', 200, 'image/jpeg');
    }

    private function subscribedStore(User $owner, int $dailyLimit): Store
    {
        $store = Store::factory()->create(['owner_user_id' => $owner->id]);
        $plan = SubscriptionPlan::factory()->create([
            'features' => ['ai_assistant' => true],
            'max_ai_messages_per_day' => $dailyLimit,
        ]);

        Subscription::create([
            'store_id' => $store->id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::ACTIVE,
            'billing_cycle' => 'monthly',
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
        ]);

        return $store;
    }
}
