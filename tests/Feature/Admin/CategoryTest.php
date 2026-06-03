<?php

namespace Tests\Feature\Admin;

use App\Domain\Product\Models\Category;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'admin', 'guard_name' => 'sanctum']);
        Storage::fake('public');
    }

    public function test_admin_can_create_product_category(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin, 'sanctum')
            ->post('/api/v1/admin/categories', [
                'name_en' => 'Electronics',
                'name_ar' => 'إلكترونيات',
                'description' => 'Devices and accessories',
                'icon' => UploadedFile::fake()->create('category.png', 50, 'image/png'),
                'sort_order' => 1,
                'is_active' => true,
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.name_en', 'Electronics')
            ->assertJsonPath('data.is_active', true)
            ->assertJsonPath('data.icon_url', fn ($value) => is_string($value) && str_contains($value, '/storage/categories/'));

        $this->assertDatabaseHas('categories', [
            'name_en' => 'Electronics',
            'slug' => 'electronics',
        ]);
        
        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Category::class,
            'event' => 'created',
        ]);
    }

    public function test_admin_can_show_category(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        
        $category = Category::factory()->create([
            'name' => 'Test Cat',
            'name_en' => 'Test Cat',
            'name_ar' => 'Test Cat',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->get("/api/v1/admin/categories/{$category->id}");

        $response->assertOk()
            ->assertJsonPath('data.name_en', 'Test Cat');
    }

    public function test_admin_can_update_product_category(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $parent = Category::factory()->create();
        $category = Category::factory()->create([
            'name_en' => 'Restaurants',
            'slug' => 'restaurants',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->post("/api/v1/admin/categories/{$category->id}", [
                '_method' => 'PUT',
                'name_en' => 'Restaurants & Cafes',
                'parent_id' => $parent->id,
                'icon' => UploadedFile::fake()->create('updated-category.png', 50, 'image/png'),
                'is_active' => false,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.name_en', 'Restaurants & Cafes')
            ->assertJsonPath('data.parent_id', $parent->id)
            ->assertJsonPath('data.is_active', false)
            ->assertJsonPath('data.icon_url', fn ($value) => is_string($value) && str_contains($value, '/storage/categories/'));

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name_en' => 'Restaurants & Cafes',
            'parent_id' => $parent->id,
            'is_active' => false,
        ]);
        
        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Category::class,
            'subject_id' => $category->id,
            'event' => 'updated',
        ]);
    }

    public function test_public_product_categories_are_listed(): void
    {
        Category::factory()->create([
            'name' => 'Electronics',
            'name_en' => 'Electronics',
            'name_ar' => 'Electronics',
            'slug' => 'electronics',
            'is_active' => true,
        ]);
        Category::factory()->inactive()->create([
            'name' => 'Hidden Category',
            'slug' => 'hidden-category',
        ]);

        $response = $this->getJson('/api/v1/categories');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Electronics');
    }

    public function test_admin_cannot_delete_category_with_children(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $parent = Category::factory()->create();
        Category::factory()->create(['parent_id' => $parent->id]);

        $response = $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/admin/categories/{$parent->id}");

        $response->assertStatus(422);

        $this->assertDatabaseHas('categories', [
            'id' => $parent->id,
        ]);
    }
}
