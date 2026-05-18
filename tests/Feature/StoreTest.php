<?php

namespace Tests\Feature;

use App\Domain\Store\Enums\StoreStatus;
use App\Domain\Store\Models\Social;
use App\Domain\Store\Models\Store;
use App\Domain\Store\Models\StoreCategory;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StoreTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'customer', 'guard_name' => 'sanctum']);
        Role::create(['name' => 'seller', 'guard_name' => 'sanctum']);
        Role::create(['name' => 'seller_pending', 'guard_name' => 'sanctum']);

        Config::set('logging.default', 'single');
        Storage::fake('public');
    }

    public function test_authenticated_user_can_create_store()
    {
        $user = User::factory()->create();
        $category = StoreCategory::factory()->create();

        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/stores', [
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
                'data',
            ]);

        $this->assertDatabaseHas('stores', [
            'name' => 'Test Store',
            'owner_user_id' => $user->id,
        ]);
    }

    public function test_store_creation_requires_authentication()
    {
        $category = StoreCategory::factory()->create();

        $response = $this->postJson('/api/v1/stores', [
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
            ->postJson('/api/v1/stores', [
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
            ->postJson('/api/v1/stores', [
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
            ->postJson('/api/v1/stores', [
                'name' => 'Test Store',
                'phone' => '+1234567890',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['categories']);
    }

    public function test_store_creation_allows_missing_verification_documents()
    {
        $user = User::factory()->create();
        $category = StoreCategory::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/stores', [
                'name' => 'Test Store',
                'phone' => '+1234567890',
                'address_line1' => '123 Main St',
                'city' => 'Test City',
                'latitude' => '40.7128',
                'longitude' => '-74.0060',
                'categories' => [$category->id],
            ]);

        $response->assertStatus(201);
    }

    public function test_store_creation_assigns_seller_pending_role()
    {
        $user = User::factory()->create();
        $user->assignRole('customer');
        $category = StoreCategory::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/stores', [
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
        $this->assertTrue($user->hasRole('customer'));
        $this->assertFalse($user->hasRole('seller'));

        $store = Store::where('owner_user_id', $user->id)->firstOrFail();
        $sellerPendingRoleId = Role::where('name', 'seller_pending')->value('id');
        $sellerRoleId = Role::where('name', 'seller')->value('id');

        $this->assertDatabaseHas('user_roles', [
            'user_id' => $user->id,
            'role_id' => $sellerPendingRoleId,
            'store_id' => null,
        ]);
        $this->assertDatabaseHas('user_roles', [
            'user_id' => $user->id,
            'role_id' => $sellerRoleId,
            'store_id' => $store->id,
        ]);
    }

    public function test_store_creation_creates_default_hours()
    {
        $user = User::factory()->create();
        $category = StoreCategory::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/stores', [
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

    public function test_public_store_listing_returns_only_active_stores_with_filters(): void
    {
        $category = StoreCategory::factory()->create();
        $otherCategory = StoreCategory::factory()->create();

        $visibleStore = Store::factory()->active()->create([
            'name' => 'Visible Store',
            'status' => StoreStatus::ACTIVE,
            'is_verified' => true,
            'rating_avg' => 4.8,
            'tax_id' => 'PRIVATE-TAX-ID',
            'admin_notes' => 'private admin note',
        ]);
        $visibleStore->categories()->attach($category->id);

        $hiddenStore = Store::factory()->pending()->create([
            'name' => 'Pending Store',
        ]);
        $hiddenStore->categories()->attach($category->id);

        $otherCategoryStore = Store::factory()->active()->create([
            'name' => 'Other Category Store',
            'status' => StoreStatus::ACTIVE,
        ]);
        $otherCategoryStore->categories()->attach($otherCategory->id);

        $response = $this->getJson('/api/v1/stores?category_id='.$category->id.'&search=Visible&is_verified=1&min_rating=4');

        $response->assertOk()
            ->assertJsonPath('data.0.id', $visibleStore->id)
            ->assertJsonPath('data.0.name', 'Visible Store')
            ->assertJsonPath('meta.total', 1)
            ->assertJsonMissingPath('data.0.tax_id')
            ->assertJsonMissingPath('data.0.admin_notes')
            ->assertJsonMissingPath('data.0.owner');
    }

    public function test_owner_can_patch_profile_on_active_store()
    {
        $owner = User::factory()->create();
        $store = Store::factory()->active()->create([
            'owner_user_id' => $owner->id,
        ]);

        $response = $this->patchStoreProfile($store, [
            'name' => 'Updated Store Name',
            'description' => 'Updated profile description',
            'email' => 'seller@example.com',
            'phone' => '+201234567890',
        ], $owner);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', __('api.store.profile_updated'))
            ->assertJsonPath('data.name', 'Updated Store Name')
            ->assertJsonPath('data.description', 'Updated profile description')
            ->assertJsonPath('data.email', 'seller@example.com')
            ->assertJsonPath('data.phone', '+201234567890')
            ->assertJsonPath('data.status', StoreStatus::ACTIVE->value);
    }

    public function test_owner_can_update_hours_on_active_store()
    {
        $owner = User::factory()->create();
        $store = Store::factory()->active()->create([
            'owner_user_id' => $owner->id,
        ]);

        $response = $this->patchStoreProfile($store, [
            'hours' => $this->validHoursPayload(),
        ], $owner);

        $response->assertOk()
            ->assertJsonPath('data.status', StoreStatus::ACTIVE->value)
            ->assertJsonCount(7, 'data.hours');

        $this->assertDatabaseHas('store_hours', [
            'store_id' => $store->id,
            'day_of_week' => 1,
            'open_time' => '09:00',
            'close_time' => '17:00',
            'is_closed' => false,
        ]);
    }

    public function test_owner_can_patch_profile_on_rejected_store_without_changing_status()
    {
        $owner = User::factory()->create();
        $reviewer = User::factory()->create();
        $rejectedAt = now()->subDay();

        $store = Store::factory()->rejected()->create([
            'owner_user_id' => $owner->id,
            'rejected_at' => $rejectedAt,
            'rejected_by' => $reviewer->id,
            'rejection_reason' => 'Missing compliance data',
        ]);

        $response = $this->patchStoreProfile($store, [
            'description' => 'Fresh public-facing copy',
        ], $owner);

        $response->assertOk()
            ->assertJsonPath('data.status', StoreStatus::REJECTED->value)
            ->assertJsonPath('data.rejection_reason', 'Missing compliance data');

        $store->refresh();

        $this->assertSame(StoreStatus::REJECTED, $store->status);
        $this->assertSame(
            $rejectedAt->toDateTimeString(),
            $store->rejected_at->toDateTimeString()
        );
        $this->assertSame($reviewer->id, $store->rejected_by);
        $this->assertSame('Missing compliance data', $store->rejection_reason);
    }

    public function test_owner_can_update_hours_on_rejected_store_without_changing_status()
    {
        $owner = User::factory()->create();
        $store = Store::factory()->rejected()->create([
            'owner_user_id' => $owner->id,
            'rejected_by' => User::factory(),
            'rejection_reason' => 'Still rejected',
        ]);

        $this->patchStoreProfile($store, [
            'hours' => $this->validHoursPayload(),
        ], $owner)->assertOk()
            ->assertJsonPath('data.status', StoreStatus::REJECTED->value);

        $store->refresh();

        $this->assertSame(StoreStatus::REJECTED, $store->status);
        $this->assertDatabaseHas('store_hours', [
            'store_id' => $store->id,
            'day_of_week' => 2,
            'open_time' => '09:00',
            'close_time' => '17:00',
            'is_closed' => false,
        ]);
    }

    public function test_patching_profile_does_not_clear_rejection_fields()
    {
        $owner = User::factory()->create();
        $reviewer = User::factory()->create();
        $rejectedAt = now()->subHours(6);

        $store = Store::factory()->rejected()->create([
            'owner_user_id' => $owner->id,
            'rejected_at' => $rejectedAt,
            'rejected_by' => $reviewer->id,
            'rejection_reason' => 'Needs review',
        ]);

        $this->patchStoreProfile($store, [
            'phone' => '+201111111111',
        ], $owner)->assertOk();

        $this->assertDatabaseHas('stores', [
            'id' => $store->id,
            'status' => StoreStatus::REJECTED->value,
            'rejected_by' => $reviewer->id,
            'rejection_reason' => 'Needs review',
        ]);

        $this->assertSame(
            $rejectedAt->toDateTimeString(),
            $store->fresh()->rejected_at->toDateTimeString()
        );
    }

    public function test_non_owner_gets_403_when_updating_store_profile()
    {
        $store = Store::factory()->active()->create();
        $otherUser = User::factory()->create();

        $this->patchStoreProfile($store, [
            'description' => 'Unauthorized update attempt',
        ], $otherUser)->assertForbidden();
    }

    public function test_suspended_store_cannot_be_updated_through_profile_endpoint()
    {
        $owner = User::factory()->create();
        $store = Store::factory()->suspended()->create([
            'owner_user_id' => $owner->id,
        ]);

        $this->patchStoreProfile($store, [
            'description' => 'Should not pass',
        ], $owner)->assertForbidden();
    }

    public function test_closed_store_cannot_be_updated_through_profile_endpoint()
    {
        $owner = User::factory()->create();
        $store = Store::factory()->create([
            'owner_user_id' => $owner->id,
            'status' => StoreStatus::CLOSED,
        ]);

        $this->patchStoreProfile($store, [
            'description' => 'Should not pass',
        ], $owner)->assertForbidden();
    }

    public function test_closed_day_is_handled_correctly()
    {
        $owner = User::factory()->create();
        $store = Store::factory()->active()->create([
            'owner_user_id' => $owner->id,
        ]);

        $hours = $this->validHoursPayload();
        $hours[0] = [
            'day_of_week' => 0,
            'open_time' => null,
            'close_time' => null,
            'is_closed' => true,
        ];

        $this->patchStoreProfile($store, [
            'hours' => $hours,
        ], $owner)->assertOk();

        $this->assertDatabaseHas('store_hours', [
            'store_id' => $store->id,
            'day_of_week' => 0,
            'open_time' => '00:00',
            'close_time' => '00:00',
            'is_closed' => true,
        ]);
    }

    public function test_invalid_hour_time_ranges_fail_validation()
    {
        $owner = User::factory()->create();
        $store = Store::factory()->active()->create([
            'owner_user_id' => $owner->id,
        ]);

        $hours = $this->validHoursPayload();
        $hours[3]['open_time'] = '18:00';
        $hours[3]['close_time'] = '09:00';

        $this->patchStoreProfile($store, [
            'hours' => $hours,
        ], $owner)->assertStatus(422)
            ->assertJsonValidationErrors(['hours.3.close_time']);
    }

    public function test_store_profile_socials_sync_works()
    {
        $owner = User::factory()->create();
        $store = Store::factory()->active()->create([
            'owner_user_id' => $owner->id,
        ]);

        $facebook = Social::create(['name' => 'Facebook', 'icon' => 'facebook.png']);
        $instagram = Social::create(['name' => 'Instagram', 'icon' => 'instagram.png']);
        $x = Social::create(['name' => 'X', 'icon' => 'x.png']);

        $store->socials()->create([
            'social_id' => $facebook->id,
            'link' => 'https://facebook.com/old-store',
        ]);
        $store->socials()->create([
            'social_id' => $instagram->id,
            'link' => 'https://instagram.com/remove-me',
        ]);

        $response = $this->patchStoreProfile($store, [
            'socials' => [
                [
                    'social_id' => $facebook->id,
                    'link' => 'https://facebook.com/new-store',
                ],
                [
                    'social_id' => $x->id,
                    'link' => 'https://x.com/new-store',
                ],
            ],
        ], $owner);

        $response->assertOk()
            ->assertJsonCount(2, 'data.socials');

        $this->assertDatabaseHas('store_socials', [
            'store_id' => $store->id,
            'social_id' => $facebook->id,
            'link' => 'https://facebook.com/new-store',
        ]);
        $this->assertDatabaseHas('store_socials', [
            'store_id' => $store->id,
            'social_id' => $x->id,
            'link' => 'https://x.com/new-store',
        ]);
        $this->assertDatabaseMissing('store_socials', [
            'store_id' => $store->id,
            'social_id' => $instagram->id,
        ]);
    }

    public function test_logo_replacement_deletes_old_file()
    {
        $owner = User::factory()->create();
        $store = Store::factory()->active()->create([
            'owner_user_id' => $owner->id,
        ]);
        $store->update([
            'logo_url' => "stores/{$store->id}/logo/old-logo.png",
        ]);

        Storage::disk('public')->put($store->logo_url, 'old-logo');

        $this->patchStoreProfile($store, [
            'logo_url' => $this->fakePngUpload('new-logo.png'),
        ], $owner)->assertOk();

        $store->refresh();

        Storage::disk('public')->assertMissing("stores/{$store->id}/logo/old-logo.png");
        Storage::disk('public')->assertExists($store->logo_url);
        $this->assertStringStartsWith("stores/{$store->id}/logo/", $store->logo_url);
    }

    public function test_banner_replacement_deletes_old_file()
    {
        $owner = User::factory()->create();
        $store = Store::factory()->active()->create([
            'owner_user_id' => $owner->id,
        ]);
        $store->update([
            'banner_url' => "stores/{$store->id}/banner/old-banner.png",
        ]);

        Storage::disk('public')->put($store->banner_url, 'old-banner');

        $this->patchStoreProfile($store, [
            'banner_url' => $this->fakePngUpload('new-banner.png'),
        ], $owner)->assertOk();

        $store->refresh();

        Storage::disk('public')->assertMissing("stores/{$store->id}/banner/old-banner.png");
        Storage::disk('public')->assertExists($store->banner_url);
        $this->assertStringStartsWith("stores/{$store->id}/banner/", $store->banner_url);
    }

    public function test_disallowed_fields_are_rejected_for_store_profile_updates()
    {
        $owner = User::factory()->create();
        $store = Store::factory()->active()->create([
            'owner_user_id' => $owner->id,
            'name' => 'Original Store Name',
            'tax_id' => 'OLD-TAX-ID',
        ]);

        $this->patchStoreProfile($store, [
            'name' => 'Allowed Name Change',
            'tax_id' => 'NEW-TAX-ID',
            'status' => StoreStatus::PENDING->value,
        ], $owner)->assertStatus(422)
            ->assertJsonValidationErrors(['tax_id', 'status']);

        $this->assertDatabaseHas('stores', [
            'id' => $store->id,
            'name' => 'Original Store Name',
            'tax_id' => 'OLD-TAX-ID',
            'status' => StoreStatus::ACTIVE->value,
        ]);
    }

    private function patchStoreProfile(Store $store, array $data, User $user)
    {
        return $this->actingAs($user, 'sanctum')
            ->withHeader('Accept', 'application/json')
            ->patch("/api/v1/stores/{$store->id}/profile", $data);
    }

    private function fakePngUpload(string $name): UploadedFile
    {
        $png = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9WnSUswAAAAASUVORK5CYII='
        );

        return UploadedFile::fake()->createWithContent($name, $png);
    }

    private function validHoursPayload(): array
    {
        return collect(range(0, 6))
            ->map(fn (int $day) => [
                'day_of_week' => $day,
                'open_time' => '09:00',
                'close_time' => '17:00',
                'is_closed' => false,
            ])
            ->all();
    }
}
