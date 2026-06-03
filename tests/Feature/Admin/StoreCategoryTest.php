<?php

namespace Tests\Feature\Admin;

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

    public function test_admin_can_create_store_category(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin, 'sanctum')
            ->post('/api/v1/admin/store-category', [
                'name_en' => 'Supermarket',
                'name_ar' => 'سوبر ماركت',
                'slug' => 'supermarket',
                'icon' => UploadedFile::fake()->create('icon.png', 50, 'image/png'),
                'image_category' => UploadedFile::fake()->create('banner.jpg', 100, 'image/jpeg'),
                'sort_order' => 1,
                'is_active' => true,
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.name_en', 'Supermarket')
            ->assertJsonPath('data.icon_url', fn ($val) => is_string($val) && str_contains($val, '/storage/store-categories/'))
            ->assertJsonPath('data.image_category_url', fn ($val) => is_string($val) && str_contains($val, '/storage/store-categories/'));

        $this->assertDatabaseHas('store_categories', [
            'name_en' => 'Supermarket',
            'slug' => 'supermarket',
        ]);
        
        $this->assertDatabaseHas('activity_log', [
            'subject_type' => StoreCategory::class,
            'event' => 'created',
        ]);
    }

    public function test_admin_can_update_store_category(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        
        $category = StoreCategory::factory()->create([
            'name_en' => 'Retail',
            'slug' => 'retail',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->post("/api/v1/admin/store-category/{$category->id}", [
                '_method' => 'PUT',
                'name_en' => 'Retail Shop',
                'is_active' => false,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.name_en', 'Retail Shop')
            ->assertJsonPath('data.is_active', false);

        $this->assertDatabaseHas('store_categories', [
            'id' => $category->id,
            'name_en' => 'Retail Shop',
            'is_active' => false,
        ]);
        
        $this->assertDatabaseHas('activity_log', [
            'subject_type' => StoreCategory::class,
            'subject_id' => $category->id,
            'event' => 'updated',
        ]);
    }

    public function test_admin_can_show_store_category(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        
        $category = StoreCategory::factory()->create([
            'name_en' => 'Show Category',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->get("/api/v1/admin/store-category/{$category->id}");

        $response->assertOk()
            ->assertJsonPath('data.name_en', 'Show Category');
    }

    public function test_admin_cannot_delete_store_category_with_stores(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        
        $category = StoreCategory::factory()->create();
        // Since we are not setting up the full Store model dependency here just for testing constraints,
        // we assume the model has a stores() relation that can block deletion. 
        // Let's create a store and attach it.
        $store = \App\Domain\Store\Models\Store::factory()->create();
        $category->stores()->attach($store->id);

        $response = $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/admin/store-category/{$category->id}");

        $response->assertStatus(422);

        $this->assertDatabaseHas('store_categories', [
            'id' => $category->id,
        ]);
    }
}
