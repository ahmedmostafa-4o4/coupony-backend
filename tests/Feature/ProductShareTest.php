<?php

namespace Tests\Feature;

use App\Domain\Product\Models\Product;
use App\Domain\Product\Models\ProductShare;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductShareTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_record_share_with_valid_platform(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/products/{$product->id}/shares", [
                'platform' => 'whatsapp',
            ]);

        $response->assertStatus(201)
            ->assertJson(['message' => 'Share recorded.']);
    }

    public function test_share_is_persisted_with_correct_data(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/products/{$product->id}/shares", [
                'platform' => 'facebook',
            ]);

        $this->assertDatabaseHas('product_shares', [
            'product_id' => $product->id,
            'user_id' => $user->id,
            'platform' => 'facebook',
        ]);
    }

    public function test_platform_is_optional(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/products/{$product->id}/shares", []);

        $response->assertStatus(201);

        $share = ProductShare::where('product_id', $product->id)->first();
        $this->assertNotNull($share);
        $this->assertNull($share->platform);
    }

    public function test_invalid_platform_returns_422(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/products/{$product->id}/shares", [
                'platform' => 'invalid_platform',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['platform']);
    }

    public function test_unauthenticated_request_returns_401(): void
    {
        $product = Product::factory()->create();

        $response = $this->postJson("/api/v1/products/{$product->id}/shares", [
            'platform' => 'whatsapp',
        ]);

        $response->assertUnauthorized();
    }

    public function test_multiple_shares_from_same_user_are_allowed(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/products/{$product->id}/shares", [
                'platform' => 'whatsapp',
            ]);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/products/{$product->id}/shares", [
                'platform' => 'facebook',
            ]);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/products/{$product->id}/shares", [
                'platform' => 'whatsapp',
            ]);

        $this->assertDatabaseCount('product_shares', 3);
        $this->assertEquals(3, ProductShare::where('user_id', $user->id)->count());
    }
}
