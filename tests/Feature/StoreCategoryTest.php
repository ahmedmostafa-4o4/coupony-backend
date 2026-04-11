<?php

namespace Tests\Feature;

use App\Domain\Store\Models\StoreCategory;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StoreCategoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'admin', 'guard_name' => 'sanctum']);
        Storage::fake('public');
    }

    public function test_admin_can_create_store_category_with_arabic_and_english_names(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin, 'sanctum')
            ->post('/api/v1/admin/store-category', [
                'name_ar' => 'إلكترونيات',
                'name_en' => 'Electronics',
                'icon' => UploadedFile::fake()->create('store-category.png', 50, 'image/png'),
                'sort_order' => 1,
                'is_active' => true,
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.name_ar', 'إلكترونيات')
            ->assertJsonPath('data.name_en', 'Electronics')
            ->assertJsonPath('data.name', 'Electronics')
            ->assertJsonPath('data.icon_url', fn($value) => is_string($value) && str_contains($value, '/storage/store-categories/'));

        $this->assertDatabaseHas('store_categories', [
            'name_ar' => 'إلكترونيات',
            'name_en' => 'Electronics',
        ]);
    }

    public function test_admin_can_update_store_category_bilingual_names(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $category = StoreCategory::factory()->create([
            'name_ar' => 'مطاعم',
            'name_en' => 'Restaurants',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->post("/api/v1/admin/store-category/{$category->id}", [
                '_method' => 'PUT',
                'name_ar' => 'مطاعم وكافيهات',
                'name_en' => 'Restaurants & Cafes',
                'icon' => UploadedFile::fake()->create('updated-store-category.png', 50, 'image/png'),
                'is_active' => false,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.name_ar', 'مطاعم وكافيهات')
            ->assertJsonPath('data.name_en', 'Restaurants & Cafes')
            ->assertJsonPath('data.is_active', false)
            ->assertJsonPath('data.icon_url', fn($value) => is_string($value) && str_contains($value, '/storage/store-categories/'));

        $this->assertDatabaseHas('store_categories', [
            'id' => $category->id,
            'name_ar' => 'مطاعم وكافيهات',
            'name_en' => 'Restaurants & Cafes',
            'is_active' => false,
        ]);
    }

    public function test_public_store_categories_are_localized_using_accept_language(): void
    {
        StoreCategory::factory()->create([
            'name_ar' => 'إلكترونيات',
            'name_en' => 'Electronics',
            'is_active' => true,
        ]);

        $englishResponse = $this->withHeader('Accept-Language', 'en')
            ->getJson('/api/v1/store-categories');

        $englishResponse->assertOk()
            ->assertHeader('Content-Language', 'en')
            ->assertJsonPath('data.0.name', 'Electronics')
            ->assertJsonPath('data.0.name_ar', 'إلكترونيات')
            ->assertJsonPath('data.0.name_en', 'Electronics');

        $arabicResponse = $this->withHeader('Accept-Language', 'ar')
            ->getJson('/api/v1/store-categories');

        $arabicResponse->assertOk()
            ->assertHeader('Content-Language', 'ar')
            ->assertJsonPath('data.0.name', 'إلكترونيات');
    }
}
