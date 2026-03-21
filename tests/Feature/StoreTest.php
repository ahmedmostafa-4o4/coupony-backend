<?php

namespace Tests\Feature;

use App\Domain\Store\Models\StoreCategory;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StoreTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'seller', 'guard_name' => 'sanctum']);
        Role::create(['name' => 'seller_pending', 'guard_name' => 'sanctum']);

        Storage::fake('public');
    }

    public function test_authenticated_user_can_create_store()
    {
        $user = User::factory()->create();
        $category = StoreCategory::factory()->create();

        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/store/create', [
            'name' => 'Test Store',
            'description' => 'A test store description',
            'phone' => '+1234567890',
            'address_line1' => '123 Main St',
            'city' => 'Test City',
            'latitude' => '40.7128',
            'longitude' => '-74.0060',
            'categories' => [$category->id],
            'verification_docs' => [
                'commercial_register' => UploadedFile::fake()->create('commercial.pdf', 1000),
                'tax_card' => UploadedFile::fake()->create('tax.pdf', 1000),
                'id_card_front' => UploadedFile::fake()->create('id_front.pdf', 1000),
                'id_card_back' => UploadedFile::fake()->create('id_back.pdf', 1000),
            ],
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
            'message',
            'data' => [
                'store',
            ],
        ]);

        $this->assertDatabaseHas('stores', [
            'name' => 'Test Store',
            'owner_user_id' => $user->id,
        ]);
    }

    public function test_store_creation_requires_authentication()
    {
        $category = StoreCategory::factory()->create();

        $response = $this->postJson('/api/v1/store/create', [
            'name' => 'Test Store',
            'phone' => '+1234567890',
            'categories' => [$category->id],
        ]);

        $response->assertStatus(401);
    }

    public function test_store_creation_requires_name()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/store/create', [
            'phone' => '+1234567890',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_store_creation_requires_phone()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/store/create', [
            'name' => 'Test Store',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['phone']);
    }

    public function test_store_creation_requires_categories()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/store/create', [
            'name' => 'Test Store',
            'phone' => '+1234567890',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['categories']);
    }

    public function test_store_creation_requires_verification_documents()
    {
        $user = User::factory()->create();
        $category = StoreCategory::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/store/create', [
            'name' => 'Test Store',
            'phone' => '+1234567890',
            'address_line1' => '123 Main St',
            'city' => 'Test City',
            'categories' => [$category->id],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['verification_docs']);
    }

    public function test_store_creation_assigns_seller_pending_role()
    {
        $user = User::factory()->create();
        $category = StoreCategory::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/store/create', [
            'name' => 'Test Store',
            'description' => 'A test store',
            'phone' => '+1234567890',
            'address_line1' => '123 Main St',
            'city' => 'Test City',
            'latitude' => '40.7128',
            'longitude' => '-74.0060',
            'categories' => [$category->id],
            'verification_docs' => [
                'commercial_register' => UploadedFile::fake()->create('commercial.pdf', 1000),
                'tax_card' => UploadedFile::fake()->create('tax.pdf', 1000),
                'id_card_front' => UploadedFile::fake()->create('id_front.pdf', 1000),
                'id_card_back' => UploadedFile::fake()->create('id_back.pdf', 1000),
            ],
        ]);

        $user->refresh();
        $this->assertTrue($user->hasRole('seller_pending'));
    }

    public function test_store_creation_creates_default_hours()
    {
        $user = User::factory()->create();
        $category = StoreCategory::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/store/create', [
            'name' => 'Test Store',
            'description' => 'A test store',
            'phone' => '+1234567890',
            'address_line1' => '123 Main St',
            'city' => 'Test City',
            'latitude' => '40.7128',
            'longitude' => '-74.0060',
            'categories' => [$category->id],
            'verification_docs' => [
                'commercial_register' => UploadedFile::fake()->create('commercial.pdf', 1000),
                'tax_card' => UploadedFile::fake()->create('tax.pdf', 1000),
                'id_card_front' => UploadedFile::fake()->create('id_front.pdf', 1000),
                'id_card_back' => UploadedFile::fake()->create('id_back.pdf', 1000),
            ],
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseCount('store_hours', 7); // 7 days of the week
    }
}
