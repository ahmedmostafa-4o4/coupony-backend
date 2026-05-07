<?php

namespace Tests\Feature;

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
                'name' => 'Electronics',
                'description' => 'Devices and accessories',
                'icon' => UploadedFile::fake()->create('category.png', 50, 'image/png'),
                'sort_order' => 1,
                'is_active' => true,
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Electronics')
            ->assertJsonPath('data.is_active', true)
            ->assertJsonPath('data.icon_url', fn($value) => is_string($value) && str_contains($value, '/storage/categories/'));

        $this->assertDatabaseHas('categories', [
            'name' => 'Electronics',
            'slug' => 'electronics',
        ]);
    }

    public function test_admin_can_update_product_category(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $parent = Category::factory()->create();
        $category = Category::factory()->create([
            'name' => 'Restaurants',
            'slug' => 'restaurants',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->post("/api/v1/admin/categories/{$category->id}", [
                '_method' => 'PUT',
                'name' => 'Restaurants & Cafes',
                'parent_id' => $parent->id,
                'icon' => UploadedFile::fake()->create('updated-category.png', 50, 'image/png'),
                'is_active' => false,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Restaurants & Cafes')
            ->assertJsonPath('data.parent_id', $parent->id)
            ->assertJsonPath('data.is_active', false)
            ->assertJsonPath('data.icon_url', fn($value) => is_string($value) && str_contains($value, '/storage/categories/'));

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Restaurants & Cafes',
            'parent_id' => $parent->id,
            'is_active' => false,
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

        $response->assertStatus(400);

        $this->assertDatabaseHas('categories', [
            'id' => $parent->id,
        ]);
    }
}
