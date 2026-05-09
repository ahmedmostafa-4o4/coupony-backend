<?php

namespace Tests\Feature;

use App\Domain\Product\Enums\ProductApprovalStatus;
use App\Domain\Product\Enums\ProductStatus;
use App\Domain\Product\Models\Category;
use App\Domain\Product\Models\OfferClaim;
use App\Domain\Product\Models\Product;
use App\Domain\Product\Models\ProductComment;
use App\Domain\Product\Models\ProductCommentLike;
use App\Domain\Product\Models\ProductFavorite;
use App\Domain\Product\Models\ProductLike;
use App\Domain\Product\Models\ProductVariant;
use App\Domain\Product\Models\ProductView;
use App\Domain\Store\Models\Store;
use App\Domain\User\Models\User;
use App\Domain\User\Models\UserPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'admin', 'guard_name' => 'sanctum']);
        Role::create(['name' => 'seller', 'guard_name' => 'sanctum']);
        Role::create(['name' => 'customer', 'guard_name' => 'sanctum']);
        Role::create(['name' => 'store_employee', 'guard_name' => 'sanctum']);

        Storage::fake('public');
        config(['logging.default' => 'null']);
    }

    public function test_seller_can_create_product_for_own_store(): void
    {
        $seller = $this->seller();
        $store = $this->storeFor($seller);
        $categories = Category::factory()->count(2)->create();

        $response = $this->actingAs($seller, 'sanctum')
            ->post("/api/v1/stores/{$store->id}/products", $this->payload([
                'category_ids' => $categories->pluck('id')->all(),
            ]));

        $response->assertCreated()
            ->assertJsonPath('data.store_id', $store->id)
            ->assertJsonPath('data.title', 'Example Product')
            ->assertJsonPath('data.approval_status', ProductApprovalStatus::PENDING->value)
            ->assertJsonPath('data.offer.type', 'fixed')
            ->assertJsonPath('data.offer.fixed_amount', '15.00')
            ->assertJsonPath('data.variants.0.original_price', '110.00')
            ->assertJsonPath('data.variants.0.price', '95.00')
            ->assertJsonPath('data.variants.0.compare_at_price', '110.00')
            ->assertJsonPath('data.variants.0.title', 'Red / XL')
            ->assertJsonPath('data.variants.0.inventory_mode', 'tracked')
            ->assertJsonPath('data.variants.0.stock_qty', 8)
            ->assertJsonPath('data.variants.0.low_stock_threshold', 2)
            ->assertJsonPath('data.variants.0.allow_backorder', false)
            ->assertJsonPath('data.variants.0.is_in_stock', true);

        $this->assertDatabaseHas('products', [
            'store_id' => $store->id,
            'slug' => 'example-product',
            'sku' => 'SKU-001',
            'status' => ProductStatus::INACTIVE->value,
            'approval_status' => ProductApprovalStatus::PENDING->value,
        ]);

        $this->assertDatabaseCount('product_images', 1);
        $this->assertDatabaseCount('product_variants', 1);
        $this->assertDatabaseCount('product_variant_attributes', 2);
        $this->assertDatabaseCount('product_categories', 2);
    }

    public function test_seller_cannot_create_product_for_another_sellers_store(): void
    {
        $seller = $this->seller();
        $otherSeller = $this->seller();
        $store = $this->storeFor($otherSeller);
        $category = Category::factory()->create();

        $response = $this->actingAs($seller, 'sanctum')
            ->post("/api/v1/stores/{$store->id}/products", $this->payload([
                'category_ids' => [$category->id],
            ]));

        $response->assertForbidden();
    }

    public function test_seller_can_list_own_store_products(): void
    {
        $seller = $this->seller();
        $store = $this->storeFor($seller);
        $otherStore = $this->storeFor($this->seller());

        Product::factory()->count(2)->create(['store_id' => $store->id]);
        Product::factory()->create(['store_id' => $otherStore->id]);

        $response = $this->actingAs($seller, 'sanctum')
            ->getJson("/api/v1/stores/{$store->id}/products");

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 2);
    }

    public function test_seller_can_view_own_product(): void
    {
        $seller = $this->seller();
        $store = $this->storeFor($seller);
        $product = Product::factory()->create(['store_id' => $store->id]);

        $response = $this->actingAs($seller, 'sanctum')
            ->getJson("/api/v1/stores/{$store->id}/products/{$product->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $product->id);
    }

    public function test_seller_can_update_own_product(): void
    {
        $seller = $this->seller();
        $store = $this->storeFor($seller);
        $product = Product::factory()->create([
            'store_id' => $store->id,
            'slug' => 'old-product',
        ]);
        $categories = Category::factory()->count(2)->create();

        $response = $this->actingAs($seller, 'sanctum')
            ->putJson("/api/v1/stores/{$store->id}/products/{$product->id}", [
                'title' => 'Updated Product',
                'slug' => 'updated-product',
                'category_ids' => [$categories[0]->id],
            ]);

        $response->assertOk()
            ->assertJsonPath('data.title', 'Updated Product')
            ->assertJsonPath('data.status', ProductStatus::INACTIVE->value);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'title' => 'Updated Product',
            'slug' => 'updated-product',
            'status' => ProductStatus::INACTIVE->value,
        ]);
        $this->assertDatabaseHas('product_categories', [
            'product_id' => $product->id,
            'category_id' => $categories[0]->id,
        ]);
    }

    public function test_seller_cannot_update_another_stores_product(): void
    {
        $seller = $this->seller();
        $otherSeller = $this->seller();
        $store = $this->storeFor($otherSeller);
        $product = Product::factory()->create(['store_id' => $store->id]);

        $response = $this->actingAs($seller, 'sanctum')
            ->putJson("/api/v1/stores/{$store->id}/products/{$product->id}", [
                'title' => 'Blocked Update',
            ]);

        $response->assertForbidden();
    }

    public function test_seller_can_delete_own_product(): void
    {
        $seller = $this->seller();
        $store = $this->storeFor($seller);
        $product = Product::factory()->create(['store_id' => $store->id]);
        ProductVariant::factory()->create(['product_id' => $product->id]);

        $response = $this->actingAs($seller, 'sanctum')
            ->deleteJson("/api/v1/stores/{$store->id}/products/{$product->id}");

        $response->assertOk()
            ->assertJsonPath('message', __('api.product.deleted'));

        $this->assertSoftDeleted('products', ['id' => $product->id]);
        $this->assertSoftDeleted('product_variants', ['product_id' => $product->id]);
    }

    public function test_public_list_only_returns_active_products(): void
    {
        Product::factory()->active()->approved()->create(['title' => 'Visible Product']);
        Product::factory()->active()->create(['title' => 'Pending Product']);

        $response = $this->getJson('/api/v1/products');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Visible Product')
            ->assertJsonPath('data.0.likes_count', 0)
            ->assertJsonPath('data.0.is_liked', false);
    }

    public function test_public_store_list_only_returns_active_products_for_that_store(): void
    {
        $store = Store::factory()->active()->create();
        $otherStore = Store::factory()->active()->create();

        Product::factory()->active()->approved()->create([
            'store_id' => $store->id,
            'title' => 'Visible Store Product',
            'is_featured' => true,
        ]);
        Product::factory()->active()->create([
            'store_id' => $store->id,
            'title' => 'Pending Store Product',
        ]);
        Product::factory()->active()->approved()->create([
            'store_id' => $otherStore->id,
            'title' => 'Other Store Product',
            'is_featured' => true,
        ]);

        $response = $this->getJson("/api/v1/public-stores/{$store->id}/products?featured=1&search=Visible");

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Visible Store Product')
            ->assertJsonPath('data.0.store.id', $store->id)
            ->assertJsonPath('meta.total', 1);
    }

    public function test_public_show_rejects_non_active_products(): void
    {
        $product = Product::factory()->create(['status' => ProductStatus::INACTIVE]);

        $response = $this->getJson("/api/v1/products/{$product->id}");

        $response->assertNotFound()
            ->assertJsonPath('message', __('api.product.not_found'));
    }

    public function test_public_show_rejects_non_approved_products(): void
    {
        $product = Product::factory()->active()->create([
            'approval_status' => ProductApprovalStatus::PENDING,
        ]);

        $response = $this->getJson("/api/v1/products/{$product->id}");

        $response->assertNotFound()
            ->assertJsonPath('message', __('api.product.not_found'));
    }

    public function test_product_resource_matches_final_contract(): void
    {
        $seller = $this->seller();
        $store = $this->storeFor($seller);
        $product = Product::factory()->active()->approved()->create(['store_id' => $store->id]);
        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'is_active' => true,
            'inventory_mode' => 'tracked',
            'stock_qty' => 7,
            'low_stock_threshold' => 2,
            'allow_backorder' => false,
        ]);

        $response = $this->actingAs($seller, 'sanctum')
            ->getJson("/api/v1/stores/{$store->id}/products/{$product->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $product->id)
            ->assertJsonPath('data.store_id', $store->id)
            ->assertJsonPath('data.title', $product->title)
            ->assertJsonPath('data.currency', 'EGP')
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.approval_status', 'approved')
            ->assertJsonPath('data.is_featured', false)
            ->assertJsonPath('data.likes_count', 0)
            ->assertJsonPath('data.is_liked', false)
            ->assertJsonPath('data.variants.0.original_price', (string) ProductVariant::where('product_id', $product->id)->first()->original_price)
            ->assertJsonPath('data.variants.0.inventory_mode', 'tracked')
            ->assertJsonPath('data.variants.0.stock_qty', 7)
            ->assertJsonPath('data.variants.0.low_stock_threshold', 2)
            ->assertJsonPath('data.variants.0.allow_backorder', false)
            ->assertJsonMissingPath('data.base_price')
            ->assertJsonMissingPath('data.compare_at_price')
            ->assertJsonMissingPath('data.sku')
            ->assertJsonMissingPath('data.published_revision_no')
            ->assertJsonMissingPath('data.approved_at')
            ->assertJsonMissingPath('data.updated_at');
    }

    public function test_authenticated_user_can_like_a_product(): void
    {
        $user = $this->customer();
        $product = Product::factory()->active()->approved()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/products/{$product->id}/likes");

        $response->assertOk()
            ->assertJsonPath('data.product_id', $product->id)
            ->assertJsonPath('data.likes_count', 1)
            ->assertJsonPath('data.is_liked', true);

        $this->assertDatabaseHas('product_likes', [
            'product_id' => $product->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_customer_can_favorite_a_product(): void
    {
        $user = $this->customer();
        $product = Product::factory()->active()->approved()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/products/{$product->id}/favorites");

        $response->assertOk()
            ->assertJsonPath('data.product_id', $product->id)
            ->assertJsonPath('data.is_favorited', true);

        $this->assertDatabaseHas('product_favorites', [
            'product_id' => $product->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_seller_can_favorite_own_product(): void
    {
        $seller = $this->seller();
        $store = $this->storeFor($seller);
        $product = Product::factory()->active()->approved()->create(['store_id' => $store->id]);

        $response = $this->actingAs($seller, 'sanctum')
            ->postJson("/api/v1/products/{$product->id}/favorites");

        $response->assertOk()
            ->assertJsonPath('data.product_id', $product->id)
            ->assertJsonPath('data.is_favorited', true);

        $this->assertDatabaseHas('product_favorites', [
            'product_id' => $product->id,
            'user_id' => $seller->id,
        ]);
    }

    public function test_duplicate_favorite_requests_do_not_create_duplicate_rows(): void
    {
        $user = $this->customer();
        $product = Product::factory()->active()->approved()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/products/{$product->id}/favorites")
            ->assertOk();

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/products/{$product->id}/favorites")
            ->assertOk()
            ->assertJsonPath('data.is_favorited', true);

        $this->assertSame(1, ProductFavorite::query()->count());
    }

    public function test_authenticated_user_can_unfavorite_a_product(): void
    {
        $user = $this->customer();
        $product = Product::factory()->active()->approved()->create();

        ProductFavorite::query()->create([
            'product_id' => $product->id,
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/v1/products/{$product->id}/favorites");

        $response->assertOk()
            ->assertJsonPath('data.product_id', $product->id)
            ->assertJsonPath('data.is_favorited', false);

        $this->assertDatabaseMissing('product_favorites', [
            'product_id' => $product->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_duplicate_unfavorite_requests_are_idempotent(): void
    {
        $user = $this->customer();
        $product = Product::factory()->active()->approved()->create();

        ProductFavorite::query()->create([
            'product_id' => $product->id,
            'user_id' => $user->id,
        ]);

        $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/v1/products/{$product->id}/favorites")
            ->assertOk();

        $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/v1/products/{$product->id}/favorites")
            ->assertOk()
            ->assertJsonPath('data.is_favorited', false);

        $this->assertSame(0, ProductFavorite::query()->count());
    }

    public function test_cannot_favorite_inactive_or_unapproved_products(): void
    {
        $user = $this->customer();
        $inactiveProduct = Product::factory()->create(['status' => ProductStatus::INACTIVE]);
        $pendingProduct = Product::factory()->active()->create([
            'approval_status' => ProductApprovalStatus::PENDING,
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/products/{$inactiveProduct->id}/favorites")
            ->assertNotFound();

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/products/{$pendingProduct->id}/favorites")
            ->assertNotFound();

        $this->assertSame(0, ProductFavorite::query()->count());
    }

    public function test_duplicate_like_requests_do_not_create_duplicate_rows(): void
    {
        $user = $this->customer();
        $product = Product::factory()->active()->approved()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/products/{$product->id}/likes")
            ->assertOk();

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/products/{$product->id}/likes")
            ->assertOk()
            ->assertJsonPath('data.likes_count', 1)
            ->assertJsonPath('data.is_liked', true);

        $this->assertSame(1, ProductLike::query()->count());
    }

    public function test_authenticated_user_can_unlike_a_product(): void
    {
        $user = $this->customer();
        $product = Product::factory()->active()->approved()->create();

        ProductLike::query()->create([
            'product_id' => $product->id,
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/v1/products/{$product->id}/likes");

        $response->assertOk()
            ->assertJsonPath('data.product_id', $product->id)
            ->assertJsonPath('data.likes_count', 0)
            ->assertJsonPath('data.is_liked', false);

        $this->assertDatabaseMissing('product_likes', [
            'product_id' => $product->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_duplicate_unlike_requests_are_idempotent(): void
    {
        $user = $this->customer();
        $product = Product::factory()->active()->approved()->create();

        ProductLike::query()->create([
            'product_id' => $product->id,
            'user_id' => $user->id,
        ]);

        $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/v1/products/{$product->id}/likes")
            ->assertOk();

        $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/v1/products/{$product->id}/likes")
            ->assertOk()
            ->assertJsonPath('data.likes_count', 0)
            ->assertJsonPath('data.is_liked', false);

        $this->assertSame(0, ProductLike::query()->count());
    }

    public function test_public_product_responses_include_like_metadata_for_authenticated_and_anonymous_users(): void
    {
        $likingUser = $this->customer();
        $otherUser = $this->customer();
        $product = Product::factory()->active()->approved()->create(['title' => 'Liked Product']);

        ProductLike::query()->create([
            'product_id' => $product->id,
            'user_id' => $likingUser->id,
        ]);

        $anonymousResponse = $this->getJson("/api/v1/products/{$product->id}");
        $anonymousResponse->assertOk()
            ->assertJsonPath('data.likes_count', 1)
            ->assertJsonPath('data.is_liked', false);

        $authenticatedListResponse = $this->actingAs($likingUser, 'sanctum')
            ->getJson('/api/v1/products');
        $authenticatedListResponse->assertOk()
            ->assertJsonPath('data.0.likes_count', 1)
            ->assertJsonPath('data.0.is_liked', true);

        $otherUserResponse = $this->actingAs($otherUser, 'sanctum')
            ->getJson("/api/v1/products/{$product->id}");
        $otherUserResponse->assertOk()
            ->assertJsonPath('data.likes_count', 1)
            ->assertJsonPath('data.is_liked', false);
    }

    public function test_product_responses_include_favorite_metadata_for_authenticated_and_anonymous_users(): void
    {
        $favoritingUser = $this->customer();
        $otherUser = $this->customer();
        $product = Product::factory()->active()->approved()->create(['title' => 'Favorited Product']);

        ProductFavorite::query()->create([
            'product_id' => $product->id,
            'user_id' => $favoritingUser->id,
        ]);

        $anonymousResponse = $this->getJson("/api/v1/products/{$product->id}");
        $anonymousResponse->assertOk()
            ->assertJsonPath('data.is_favorited', false);

        $authenticatedListResponse = $this->actingAs($favoritingUser, 'sanctum')
            ->getJson('/api/v1/products');
        $authenticatedListResponse->assertOk()
            ->assertJsonPath('data.0.is_favorited', true);

        $otherUserResponse = $this->actingAs($otherUser, 'sanctum')
            ->getJson("/api/v1/products/{$product->id}");
        $otherUserResponse->assertOk()
            ->assertJsonPath('data.is_favorited', false);
    }

    public function test_authenticated_user_can_list_favorite_products_in_latest_favorite_order(): void
    {
        $user = $this->customer();
        $olderFavorite = Product::factory()->active()->approved()->create(['title' => 'Older Favorite']);
        $newerFavorite = Product::factory()->active()->approved()->create(['title' => 'Newer Favorite']);
        $otherUsersFavorite = Product::factory()->active()->approved()->create(['title' => 'Other User Favorite']);

        $this->recordProductFavorite($user, $olderFavorite, now()->subHour());
        $this->recordProductFavorite($user, $newerFavorite, now()->subMinute());
        $this->recordProductFavorite($this->customer(), $otherUsersFavorite, now());

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/me/favorite-products?per_page=10');

        $response->assertOk()
            ->assertJsonPath('message', __('api.product.favorite_products_retrieved'))
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('data.0.id', $newerFavorite->id)
            ->assertJsonPath('data.0.is_favorited', true)
            ->assertJsonPath('data.1.id', $olderFavorite->id)
            ->assertJsonPath('data.1.is_favorited', true);
    }

    public function test_authenticated_user_can_fetch_product_recommendations(): void
    {
        $user = $this->customer();
        $seedProduct = Product::factory()->active()->approved()->create(['title' => 'Seed Product']);
        $recommendedProduct = Product::factory()->active()->approved()->create(['title' => 'Recommended Product']);

        $this->recordProductView($user, $seedProduct);

        Http::fake([
            'https://ahmedmostafa56-ml-recommendation-service.hf.space/recommend' => Http::response([
                'user_id' => $user->id,
                'recommended_ids' => [$recommendedProduct->id],
            ]),
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/me/recommendations/products?limit=1');

        $response->assertOk()
            ->assertJsonPath('message', __('api.product.recommendations_retrieved'))
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $recommendedProduct->id);

        Http::assertSent(function (HttpRequest $request) use ($user, $seedProduct) {
            return $request->url() === 'https://ahmedmostafa56-ml-recommendation-service.hf.space/recommend'
                && $request['user_id'] === $user->id
                && $request['recent_product_ids'] === [$seedProduct->id]
                && $request['limit'] === 1;
        });
    }

    public function test_recommendations_preserve_ml_rank_order(): void
    {
        $user = $this->customer();
        $seedProduct = Product::factory()->active()->approved()->create();
        $firstRecommended = Product::factory()->active()->approved()->create(['title' => 'First']);
        $secondRecommended = Product::factory()->active()->approved()->create(['title' => 'Second']);

        $this->recordProductLike($user, $seedProduct);

        Http::fake([
            'https://ahmedmostafa56-ml-recommendation-service.hf.space/recommend' => Http::response([
                'user_id' => $user->id,
                'recommended_ids' => [$secondRecommended->id, $firstRecommended->id],
            ]),
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/me/recommendations/products?limit=2');

        $response->assertOk()
            ->assertJsonPath('data.0.id', $secondRecommended->id)
            ->assertJsonPath('data.1.id', $firstRecommended->id);
    }

    public function test_recommendations_filter_non_public_products_returned_by_ml(): void
    {
        $user = $this->customer();
        $seedProduct = Product::factory()->active()->approved()->create();
        $inactiveProduct = Product::factory()->create(['status' => ProductStatus::INACTIVE]);
        $pendingProduct = Product::factory()->active()->create(['approval_status' => ProductApprovalStatus::PENDING]);
        $visibleProduct = Product::factory()->active()->approved()->create();

        $this->recordProductView($user, $seedProduct);

        Http::fake([
            'https://ahmedmostafa56-ml-recommendation-service.hf.space/recommend' => Http::response([
                'user_id' => $user->id,
                'recommended_ids' => [$inactiveProduct->id, $pendingProduct->id, $visibleProduct->id],
            ]),
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/me/recommendations/products?limit=1');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $visibleProduct->id);
    }

    public function test_recommendations_exclude_seed_products_even_if_ml_returns_them(): void
    {
        $user = $this->customer();
        $seedProduct = Product::factory()->active()->approved()->create(['title' => 'Seen Already']);
        $recommendedProduct = Product::factory()->active()->approved()->create(['title' => 'Fresh Pick']);

        $this->recordProductView($user, $seedProduct);

        Http::fake([
            'https://ahmedmostafa56-ml-recommendation-service.hf.space/recommend' => Http::response([
                'user_id' => $user->id,
                'recommended_ids' => [$seedProduct->id, $recommendedProduct->id],
            ]),
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/me/recommendations/products?limit=1');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $recommendedProduct->id);
    }

    public function test_recommendations_top_up_with_popular_fallback_when_ml_returns_too_few_products(): void
    {
        $user = $this->customer();
        $seedProduct = Product::factory()->active()->approved()->create();
        $mlRecommended = Product::factory()->active()->approved()->create();
        $fallbackOne = Product::factory()->active()->approved()->create(['created_at' => now()->subMinute()]);
        $fallbackTwo = Product::factory()->active()->approved()->create(['created_at' => now()->subMinutes(2)]);

        ProductLike::query()->create(['product_id' => $fallbackOne->id, 'user_id' => $this->customer()->id]);
        ProductLike::query()->create(['product_id' => $fallbackTwo->id, 'user_id' => $this->customer()->id]);
        $this->recordProductComment($user, $seedProduct);

        Http::fake([
            'https://ahmedmostafa56-ml-recommendation-service.hf.space/recommend' => Http::response([
                'user_id' => $user->id,
                'recommended_ids' => [$mlRecommended->id],
            ]),
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/me/recommendations/products?limit=3');

        $response->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.id', $mlRecommended->id);

        $this->assertSame(
            [$mlRecommended->id, $fallbackOne->id, $fallbackTwo->id],
            array_column($response->json('data'), 'id')
        );
    }

    public function test_recommendations_fall_back_to_popular_products_when_ml_service_fails(): void
    {
        $user = $this->customer();
        $seedProduct = Product::factory()->active()->approved()->create();
        $popularProduct = Product::factory()->active()->approved()->create();
        $secondaryPopularProduct = Product::factory()->active()->approved()->create(['created_at' => now()->subMinute()]);

        $this->recordOfferClaim($user, $seedProduct);
        ProductLike::query()->create(['product_id' => $popularProduct->id, 'user_id' => $this->customer()->id]);
        ProductLike::query()->create(['product_id' => $secondaryPopularProduct->id, 'user_id' => $this->customer()->id]);

        Http::fake([
            'https://ahmedmostafa56-ml-recommendation-service.hf.space/recommend' => Http::response(['message' => 'upstream error'], 500),
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/me/recommendations/products?limit=2');

        $response->assertOk()
            ->assertJsonCount(2, 'data');

        $this->assertSame(
            [$popularProduct->id, $secondaryPopularProduct->id],
            array_column($response->json('data'), 'id')
        );
    }

    public function test_recommendations_fall_back_when_ml_returns_invalid_payload_or_empty_results(): void
    {
        $user = $this->customer();
        $seedProduct = Product::factory()->active()->approved()->create();
        $popularProduct = Product::factory()->active()->approved()->create();

        $this->recordProductLike($user, $seedProduct);
        ProductLike::query()->create(['product_id' => $popularProduct->id, 'user_id' => $this->customer()->id]);

        Http::fake([
            'https://ahmedmostafa56-ml-recommendation-service.hf.space/recommend' => Http::response('not-json', 200, [
                'Content-Type' => 'application/json',
            ]),
        ]);

        $invalidPayloadResponse = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/me/recommendations/products?limit=1');

        $invalidPayloadResponse->assertOk()
            ->assertJsonPath('data.0.id', $popularProduct->id);

        Http::fake([
            'https://ahmedmostafa56-ml-recommendation-service.hf.space/recommend' => Http::response([
                'user_id' => $user->id,
                'recommended_ids' => [],
            ]),
        ]);

        $emptyResultResponse = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/me/recommendations/products?limit=1');

        $emptyResultResponse->assertOk()
            ->assertJsonPath('data.0.id', $popularProduct->id);
    }

    public function test_recommendations_fall_back_when_ml_request_times_out(): void
    {
        $user = $this->customer();
        $seedProduct = Product::factory()->active()->approved()->create();
        $popularProduct = Product::factory()->active()->approved()->create();

        $this->recordProductComment($user, $seedProduct);
        ProductLike::query()->create(['product_id' => $popularProduct->id, 'user_id' => $this->customer()->id]);

        Http::fake([
            'https://ahmedmostafa56-ml-recommendation-service.hf.space/recommend' => function () {
                throw new ConnectionException('timeout');
            },
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/me/recommendations/products?limit=1');

        $response->assertOk()
            ->assertJsonPath('data.0.id', $popularProduct->id);
    }

    public function test_recommendations_skip_ml_when_personalization_is_disabled(): void
    {
        $user = $this->customer();
        $popularProduct = Product::factory()->active()->approved()->create();
        UserPreference::factory()->create([
            'user_id' => $user->id,
            'enable_personalized_recommendations' => false,
        ]);

        ProductLike::query()->create(['product_id' => $popularProduct->id, 'user_id' => $this->customer()->id]);

        Http::fake();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/me/recommendations/products?limit=1');

        $response->assertOk()
            ->assertJsonPath('data.0.id', $popularProduct->id);

        Http::assertNothingSent();
    }

    public function test_recommendation_seed_excludes_views_when_browsing_history_tracking_is_disabled(): void
    {
        $user = $this->customer();
        $viewedProduct = Product::factory()->active()->approved()->create();
        $likedProduct = Product::factory()->active()->approved()->create();
        $recommendedProduct = Product::factory()->active()->approved()->create();

        UserPreference::factory()->create([
            'user_id' => $user->id,
            'browsing_history_tracking' => false,
        ]);

        $this->recordProductView($user, $viewedProduct);
        $this->recordProductLike($user, $likedProduct);

        Http::fake([
            'https://ahmedmostafa56-ml-recommendation-service.hf.space/recommend' => function (HttpRequest $request) use ($user, $likedProduct, $recommendedProduct) {
                $this->assertSame($user->id, $request['user_id']);
                $this->assertSame([$likedProduct->id], $request['recent_product_ids']);

                return Http::response([
                    'user_id' => $user->id,
                    'recommended_ids' => [$recommendedProduct->id],
                ]);
            },
        ]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/me/recommendations/products?limit=1')
            ->assertOk()
            ->assertJsonPath('data.0.id', $recommendedProduct->id);
    }

    public function test_recommendation_seed_uses_recent_interactions_ordered_by_recency(): void
    {
        $user = $this->customer();
        $viewedProduct = Product::factory()->active()->approved()->create();
        $likedProduct = Product::factory()->active()->approved()->create();
        $commentedProduct = Product::factory()->active()->approved()->create();
        $claimedProduct = Product::factory()->active()->approved()->create();
        $recommendedProduct = Product::factory()->active()->approved()->create();

        $this->recordProductView($user, $viewedProduct, now()->subMinutes(40));
        $this->recordProductLike($user, $likedProduct, now()->subMinutes(30));
        $this->recordProductComment($user, $commentedProduct, now()->subMinutes(20));
        $this->recordOfferClaim($user, $claimedProduct, now()->subMinutes(10));

        Http::fake([
            'https://ahmedmostafa56-ml-recommendation-service.hf.space/recommend' => function (HttpRequest $request) use ($user, $viewedProduct, $likedProduct, $commentedProduct, $claimedProduct, $recommendedProduct) {
                $this->assertSame($user->id, $request['user_id']);
                $this->assertSame(
                    [$claimedProduct->id, $commentedProduct->id, $likedProduct->id, $viewedProduct->id],
                    $request['recent_product_ids']
                );

                return Http::response([
                    'user_id' => $user->id,
                    'recommended_ids' => [$recommendedProduct->id],
                ]);
            },
        ]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/me/recommendations/products?limit=1')
            ->assertOk()
            ->assertJsonPath('data.0.id', $recommendedProduct->id);
    }

    public function test_anonymous_users_cannot_access_product_recommendations(): void
    {
        $this->getJson('/api/v1/me/recommendations/products')
            ->assertUnauthorized();
    }

    public function test_user_can_list_only_their_liked_products(): void
    {
        $user = $this->customer();
        $otherUser = $this->customer();
        $likedProduct = Product::factory()->active()->approved()->create(['title' => 'Mine']);
        $alsoLikedProduct = Product::factory()->active()->approved()->create(['title' => 'Mine Too']);
        $otherUsersProduct = Product::factory()->active()->approved()->create(['title' => 'Someone Else']);
        $hiddenProduct = Product::factory()->active()->create(['title' => 'Pending Product']);

        ProductLike::query()->create([
            'product_id' => $likedProduct->id,
            'user_id' => $user->id,
        ]);
        ProductLike::query()->create([
            'product_id' => $alsoLikedProduct->id,
            'user_id' => $user->id,
        ]);
        ProductLike::query()->create([
            'product_id' => $otherUsersProduct->id,
            'user_id' => $otherUser->id,
        ]);
        ProductLike::query()->create([
            'product_id' => $hiddenProduct->id,
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/me/liked-products');

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('data.0.is_liked', true)
            ->assertJsonPath('data.1.is_liked', true);
    }

    public function test_non_public_products_cannot_be_liked(): void
    {
        $user = $this->customer();
        $inactiveProduct = Product::factory()->create(['status' => ProductStatus::INACTIVE]);
        $pendingProduct = Product::factory()->active()->create([
            'approval_status' => ProductApprovalStatus::PENDING,
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/products/{$inactiveProduct->id}/likes")
            ->assertNotFound();

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/products/{$pendingProduct->id}/likes")
            ->assertNotFound();

        $this->assertSame(0, ProductLike::query()->count());
    }

    public function test_authenticated_user_can_create_one_product_review_and_rating_summary_updates(): void
    {
        $user = $this->customer();
        $product = Product::factory()->active()->approved()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/products/{$product->id}/comments", [
                'rating' => 4,
                'body' => 'Solid deal and the product matched the description.',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.product_id', $product->id)
            ->assertJsonPath('data.rating', 4)
            ->assertJsonPath('data.likes_count', 0)
            ->assertJsonPath('data.is_liked', false);

        $this->assertDatabaseHas('product_comments', [
            'product_id' => $product->id,
            'user_id' => $user->id,
            'review_user_id' => $user->id,
            'rating' => 4,
            'status' => ProductComment::STATUS_VISIBLE,
        ]);

        $product->refresh();
        $this->assertSame('4.00', (string) $product->rating_avg);
        $this->assertSame(1, $product->rating_count);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/products/{$product->id}/comments", [
                'rating' => 5,
                'body' => 'Trying a duplicate review.',
            ])
            ->assertUnprocessable()
            ->assertJsonPath('message', __('api.product.comment_duplicate'));
    }

    public function test_product_review_validation_and_public_product_gate(): void
    {
        $user = $this->customer();
        $product = Product::factory()->active()->approved()->create();
        $pendingProduct = Product::factory()->active()->create([
            'approval_status' => ProductApprovalStatus::PENDING,
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/products/{$product->id}/comments", [
                'rating' => 6,
                'body' => '',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['rating', 'body']);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/products/{$pendingProduct->id}/comments", [
                'rating' => 5,
                'body' => 'Hidden product review.',
            ])
            ->assertNotFound();
    }

    public function test_nested_replies_are_returned_with_public_comments(): void
    {
        $reviewer = $this->customer();
        $replier = $this->customer();
        $product = Product::factory()->active()->approved()->create();

        $reviewId = $this->actingAs($reviewer, 'sanctum')
            ->postJson("/api/v1/products/{$product->id}/comments", [
                'rating' => 5,
                'body' => 'Excellent.',
            ])
            ->json('data.id');

        $replyId = $this->actingAs($replier, 'sanctum')
            ->postJson("/api/v1/products/{$product->id}/comments/{$reviewId}/replies", [
                'body' => 'Thanks for the details.',
            ])
            ->assertCreated()
            ->assertJsonPath('data.rating', null)
            ->json('data.id');

        $this->actingAs($reviewer, 'sanctum')
            ->postJson("/api/v1/products/{$product->id}/comments/{$replyId}/replies", [
                'body' => 'Happy to help.',
            ])
            ->assertCreated();

        $response = $this->getJson("/api/v1/products/{$product->id}/comments");

        $response->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', $reviewId)
            ->assertJsonPath('data.0.replies.0.id', $replyId)
            ->assertJsonPath('data.0.replies.0.replies.0.body', 'Happy to help.');
    }

    public function test_comment_like_and_unlike_are_idempotent(): void
    {
        $user = $this->customer();
        $product = Product::factory()->active()->approved()->create();
        $comment = ProductComment::query()->create([
            'product_id' => $product->id,
            'user_id' => $this->customer()->id,
            'rating' => 5,
            'body' => 'Worth it.',
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/product-comments/{$comment->id}/likes")
            ->assertOk()
            ->assertJsonPath('data.likes_count', 1)
            ->assertJsonPath('data.is_liked', true);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/product-comments/{$comment->id}/likes")
            ->assertOk()
            ->assertJsonPath('data.likes_count', 1);

        $this->assertSame(1, ProductCommentLike::query()->count());

        $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/v1/product-comments/{$comment->id}/likes")
            ->assertOk()
            ->assertJsonPath('data.likes_count', 0)
            ->assertJsonPath('data.is_liked', false);

        $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/v1/product-comments/{$comment->id}/likes")
            ->assertOk()
            ->assertJsonPath('data.likes_count', 0);
    }

    public function test_owner_updates_deletes_and_admin_hides_comments_with_rating_recalculation(): void
    {
        $owner = $this->customer();
        $otherUser = $this->customer();
        $admin = $this->admin();
        $product = Product::factory()->active()->approved()->create();

        $commentId = $this->actingAs($owner, 'sanctum')
            ->postJson("/api/v1/products/{$product->id}/comments", [
                'rating' => 2,
                'body' => 'Needs work.',
            ])
            ->json('data.id');

        $this->actingAs($otherUser, 'sanctum')
            ->patchJson("/api/v1/product-comments/{$commentId}", ['body' => 'Nope'])
            ->assertNotFound();

        $this->actingAs($owner, 'sanctum')
            ->patchJson("/api/v1/product-comments/{$commentId}", [
                'rating' => 5,
                'body' => 'Actually great after trying again.',
            ])
            ->assertOk()
            ->assertJsonPath('data.rating', 5);

        $product->refresh();
        $this->assertSame('5.00', (string) $product->rating_avg);

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/admin/product-comments/{$commentId}/hide")
            ->assertOk()
            ->assertJsonPath('data.status', ProductComment::STATUS_HIDDEN);

        $this->getJson("/api/v1/products/{$product->id}/comments")
            ->assertOk()
            ->assertJsonPath('meta.total', 0);

        $product->refresh();
        $this->assertSame('0.00', (string) $product->rating_avg);
        $this->assertSame(0, $product->rating_count);

        $secondCommentId = $this->actingAs($otherUser, 'sanctum')
            ->postJson("/api/v1/products/{$product->id}/comments", [
                'rating' => 4,
                'body' => 'Another review for admin deletion.',
            ])
            ->assertCreated()
            ->json('data.id');

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/product-comments/{$secondCommentId}")
            ->assertOk();

        $this->assertSoftDeleted('product_comments', ['id' => $secondCommentId]);
    }

    public function test_category_sync_works(): void
    {
        $seller = $this->seller();
        $store = $this->storeFor($seller);
        $categories = Category::factory()->count(2)->create();

        $response = $this->actingAs($seller, 'sanctum')
            ->post("/api/v1/stores/{$store->id}/products", $this->payload([
                'category_ids' => $categories->pluck('id')->all(),
            ]));

        $productId = $response->json('data.id');

        $this->assertDatabaseCount('product_categories', 2);
        $this->assertDatabaseHas('product_categories', [
            'product_id' => $productId,
            'category_id' => $categories[0]->id,
        ]);
        $this->assertDatabaseHas('product_categories', [
            'product_id' => $productId,
            'category_id' => $categories[1]->id,
        ]);
    }

    public function test_seller_create_without_slug_and_sku_generates_identifiers_for_product_and_variants(): void
    {
        $seller = $this->seller();
        $store = $this->storeFor($seller);

        $response = $this->actingAs($seller, 'sanctum')
            ->postJson("/api/v1/stores/{$store->id}/products", $this->payload([
                'title' => 'جزمة',
                'slug' => null,
                'sku' => null,
                'images' => [],
                'variants' => [
                    [
                        'title' => 'Black / 42',
                        'option_summary' => 'Color: Black, Size: 42',
                        'sku' => null,
                        'barcode' => '123456789',
                        'original_price' => 110,
                        'currency' => 'EGP',
                        'sort_order' => 0,
                        'is_default' => true,
                        'is_active' => true,
                        'inventory_mode' => 'tracked',
                        'stock_qty' => 8,
                        'low_stock_threshold' => 2,
                        'allow_backorder' => false,
                        'attributes' => [
                            [
                                'attribute_name' => 'color',
                                'attribute_value' => 'black',
                                'sort_order' => 0,
                            ],
                            [
                                'attribute_name' => 'size',
                                'attribute_value' => '42',
                                'sort_order' => 1,
                            ],
                        ],
                    ],
                ],
            ]));

        $productId = $response->json('data.id');

        $response->assertCreated()
            ->assertJsonPath('data.slug', 'gazma')
            ->assertJsonPath('data.variants.0.sku', 'VAR-SHO-GAZ-BLK-42');

        $this->assertDatabaseHas('products', [
            'id' => $productId,
            'slug' => 'gazma',
            'sku' => 'PRD-SHO-GAZ',
        ]);
        $this->assertDatabaseHas('product_variants', [
            'product_id' => $productId,
            'sku' => 'VAR-SHO-GAZ-BLK-42',
        ]);
    }

    public function test_variant_creation_works(): void
    {
        $seller = $this->seller();
        $store = $this->storeFor($seller);
        $category = Category::factory()->create();

        $response = $this->actingAs($seller, 'sanctum')
            ->post("/api/v1/stores/{$store->id}/products", $this->payload([
                'category_ids' => [$category->id],
            ]));

        $productId = $response->json('data.id');

        $this->assertDatabaseHas('product_variants', [
            'product_id' => $productId,
            'title' => 'Red / XL',
            'sku' => 'SKU-001-RED-XL',
            'original_price' => 110,
            'price' => 95,
            'compare_at_price' => 110,
            'is_default' => true,
            'inventory_mode' => 'tracked',
            'stock_qty' => 8,
            'low_stock_threshold' => 2,
            'allow_backorder' => false,
        ]);

        $variantId = ProductVariant::where('product_id', $productId)->value('id');

        $this->assertDatabaseHas('product_variant_attributes', [
            'variant_id' => $variantId,
            'attribute_name' => 'color',
            'attribute_value' => 'red',
        ]);
    }

    public function test_only_one_default_variant_is_enforced(): void
    {
        $seller = $this->seller();
        $store = $this->storeFor($seller);
        $category = Category::factory()->create();
        $payload = $this->payload([
            'category_ids' => [$category->id],
            'images' => [],
            'variants' => [
                [
                    'title' => 'Red / XL',
                    'option_summary' => 'Color: Red, Size: XL',
                    'sku' => 'SKU-001-RED-XL',
                    'barcode' => '123456789',
                    'original_price' => 110,
                    'currency' => 'EGP',
                    'sort_order' => 0,
                    'is_default' => true,
                    'is_active' => true,
                    'attributes' => [],
                ],
                [
                    'title' => 'Blue / L',
                    'option_summary' => 'Color: Blue, Size: L',
                    'sku' => 'SKU-001-BLUE-L',
                    'barcode' => '987654321',
                    'original_price' => 115,
                    'currency' => 'EGP',
                    'sort_order' => 1,
                    'is_default' => true,
                    'is_active' => true,
                    'attributes' => [],
                ],
            ],
        ]);

        $response = $this->actingAs($seller, 'sanctum')
            ->postJson("/api/v1/stores/{$store->id}/products", $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['variants']);
    }

    public function test_product_offer_is_required_on_create(): void
    {
        $seller = $this->seller();
        $store = $this->storeFor($seller);

        $response = $this->actingAs($seller, 'sanctum')
            ->postJson("/api/v1/stores/{$store->id}/products", $this->payload([
                'offer' => null,
                'images' => [],
            ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['offer']);
    }

    public function test_fixed_offer_requires_fixed_amount(): void
    {
        $seller = $this->seller();
        $store = $this->storeFor($seller);

        $response = $this->actingAs($seller, 'sanctum')
            ->postJson("/api/v1/stores/{$store->id}/products", $this->payload([
                'images' => [],
                'offer' => [
                    'type' => 'fixed',
                    'status' => 'active',
                    'fixed_amount' => null,
                ],
            ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['offer.fixed_amount']);
    }

    public function test_percentage_offer_requires_percentage_value(): void
    {
        $seller = $this->seller();
        $store = $this->storeFor($seller);

        $response = $this->actingAs($seller, 'sanctum')
            ->postJson("/api/v1/stores/{$store->id}/products", $this->payload([
                'images' => [],
                'offer' => [
                    'type' => 'percentage',
                    'status' => 'active',
                    'fixed_amount' => null,
                    'percentage_value' => null,
                    'max_discount' => null,
                ],
            ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['offer.percentage_value']);
    }

    public function test_fixed_offer_resolves_variant_prices_from_original_price(): void
    {
        $seller = $this->seller();
        $store = $this->storeFor($seller);

        $response = $this->actingAs($seller, 'sanctum')
            ->postJson("/api/v1/stores/{$store->id}/products", $this->payload([
                'images' => [],
                'offer' => [
                    'type' => 'fixed',
                    'status' => 'active',
                    'fixed_amount' => 15,
                ],
            ]));

        $response->assertCreated()
            ->assertJsonPath('data.variants.0.original_price', '110.00')
            ->assertJsonPath('data.variants.0.price', '95.00')
            ->assertJsonPath('data.variants.0.compare_at_price', '110.00');
    }

    public function test_percentage_offer_resolves_variant_prices_from_original_price(): void
    {
        $seller = $this->seller();
        $store = $this->storeFor($seller);

        $response = $this->actingAs($seller, 'sanctum')
            ->postJson("/api/v1/stores/{$store->id}/products", $this->payload([
                'images' => [],
                'offer' => [
                    'type' => 'percentage',
                    'status' => 'active',
                    'fixed_amount' => null,
                    'percentage_value' => 25,
                    'max_discount' => null,
                ],
            ]));

        $response->assertCreated()
            ->assertJsonPath('data.variants.0.original_price', '110.00')
            ->assertJsonPath('data.variants.0.price', '82.50')
            ->assertJsonPath('data.variants.0.compare_at_price', '110.00');
    }

    public function test_negative_fixed_offer_final_price_is_rejected(): void
    {
        $seller = $this->seller();
        $store = $this->storeFor($seller);

        $response = $this->actingAs($seller, 'sanctum')
            ->postJson("/api/v1/stores/{$store->id}/products", $this->payload([
                'images' => [],
                'variants' => [
                    [
                        'title' => 'Low Price Variant',
                        'option_summary' => 'Low price',
                        'sku' => 'LOW-PRICE',
                        'barcode' => '111111111',
                        'original_price' => 10,
                        'currency' => 'EGP',
                        'sort_order' => 0,
                        'is_default' => true,
                        'is_active' => true,
                        'inventory_mode' => 'tracked',
                        'stock_qty' => 2,
                        'low_stock_threshold' => 1,
                        'allow_backorder' => false,
                        'attributes' => [],
                    ]
                ],
                'offer' => [
                    'type' => 'fixed',
                    'status' => 'active',
                    'fixed_amount' => 15,
                ],
            ]));

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Resolved fixed-offer price cannot be negative.');
    }

    public function test_buy_x_get_y_keeps_reference_pricing_behavior(): void
    {
        $seller = $this->seller();
        $store = $this->storeFor($seller);

        $response = $this->actingAs($seller, 'sanctum')
            ->postJson("/api/v1/stores/{$store->id}/products", $this->payload([
                'images' => [],
                'offer' => [
                    'type' => 'buy_x_get_y',
                    'status' => 'active',
                    'buy_qty' => 2,
                    'get_qty' => 1,
                    'allow_mix_buy_variants' => true,
                    'allow_mix_reward_variants' => false,
                    'buy_variant_skus' => ['SKU-001-RED-XL'],
                    'reward_variant_skus' => ['SKU-001-RED-XL'],
                ],
            ]));

        $response->assertCreated()
            ->assertJsonPath('data.variants.0.original_price', '110.00')
            ->assertJsonPath('data.variants.0.price', '110.00')
            ->assertJsonPath('data.variants.0.compare_at_price', null);
    }

    public function test_buy_x_get_y_offer_accepts_generated_variant_skus_on_seller_create(): void
    {
        $seller = $this->seller();
        $store = $this->storeFor($seller);

        $response = $this->actingAs($seller, 'sanctum')
            ->postJson("/api/v1/stores/{$store->id}/products", $this->payload([
                'title' => 'Running Shoes',
                'slug' => null,
                'sku' => null,
                'images' => [],
                'variants' => [
                    [
                        'title' => 'Black / 42',
                        'option_summary' => 'Color: Black, Size: 42',
                        'sku' => null,
                        'barcode' => '123456789',
                        'original_price' => 110,
                        'currency' => 'EGP',
                        'sort_order' => 0,
                        'is_default' => true,
                        'is_active' => true,
                        'inventory_mode' => 'tracked',
                        'stock_qty' => 8,
                        'low_stock_threshold' => 2,
                        'allow_backorder' => false,
                        'attributes' => [
                            ['attribute_name' => 'color', 'attribute_value' => 'black', 'sort_order' => 0],
                            ['attribute_name' => 'size', 'attribute_value' => '42', 'sort_order' => 1],
                        ],
                    ],
                ],
                'offer' => [
                    'type' => 'buy_x_get_y',
                    'status' => 'active',
                    'buy_qty' => 1,
                    'get_qty' => 1,
                    'allow_mix_buy_variants' => false,
                    'allow_mix_reward_variants' => false,
                    'buy_variant_skus' => ['VAR-SHO-RUN-BLK-42'],
                    'reward_variant_skus' => ['VAR-SHO-RUN-BLK-42'],
                ],
            ]));

        $response->assertCreated()
            ->assertJsonPath('data.offer.type', 'buy_x_get_y')
            ->assertJsonPath('data.variants.0.sku', 'VAR-SHO-RUN-BLK-42');
    }

    public function test_duplicate_generated_variant_skus_are_validated_from_prepared_values(): void
    {
        $seller = $this->seller();
        $store = $this->storeFor($seller);

        $response = $this->actingAs($seller, 'sanctum')
            ->postJson("/api/v1/stores/{$store->id}/products", $this->payload([
                'title' => 'جزمة',
                'slug' => null,
                'sku' => null,
                'images' => [],
                'variants' => [
                    [
                        'title' => 'Black / 42',
                        'option_summary' => 'Color: Black, Size: 42',
                        'sku' => 'VAR-SHO-GAZ-BLK-42',
                        'barcode' => '123456789',
                        'original_price' => 110,
                        'currency' => 'EGP',
                        'sort_order' => 0,
                        'is_default' => true,
                        'is_active' => true,
                        'inventory_mode' => 'tracked',
                        'stock_qty' => 8,
                        'low_stock_threshold' => 2,
                        'allow_backorder' => false,
                        'attributes' => [
                            ['attribute_name' => 'لون', 'attribute_value' => 'أسود', 'sort_order' => 0],
                            ['attribute_name' => 'مقاس', 'attribute_value' => '42', 'sort_order' => 1],
                        ],
                    ],
                    [
                        'title' => 'Black / 42 Copy',
                        'option_summary' => 'Color: Black, Size: 42',
                        'sku' => 'var-sho-gaz-blk-42',
                        'barcode' => '987654321',
                        'original_price' => 115,
                        'currency' => 'EGP',
                        'sort_order' => 1,
                        'is_default' => false,
                        'is_active' => true,
                        'inventory_mode' => 'tracked',
                        'stock_qty' => 5,
                        'low_stock_threshold' => 1,
                        'allow_backorder' => false,
                        'attributes' => [
                            ['attribute_name' => 'لون', 'attribute_value' => 'أسود', 'sort_order' => 0],
                            ['attribute_name' => 'مقاس', 'attribute_value' => '42', 'sort_order' => 1],
                        ],
                    ],
                ],
            ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['variants']);
    }

    public function test_seller_cannot_spoof_compare_at_price_for_computed_offer_types(): void
    {
        $seller = $this->seller();
        $store = $this->storeFor($seller);

        $response = $this->actingAs($seller, 'sanctum')
            ->postJson("/api/v1/stores/{$store->id}/products", $this->payload([
                'images' => [],
                'variants' => [
                    [
                        'title' => 'Spoofed Variant',
                        'option_summary' => 'Spoofed',
                        'sku' => 'SPOOF-1',
                        'barcode' => '222222222',
                        'original_price' => 100,
                        'price' => 1,
                        'compare_at_price' => 999,
                        'currency' => 'EGP',
                        'sort_order' => 0,
                        'is_default' => true,
                        'is_active' => true,
                        'inventory_mode' => 'tracked',
                        'stock_qty' => 5,
                        'low_stock_threshold' => 1,
                        'allow_backorder' => false,
                        'attributes' => [],
                    ]
                ],
            ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['variants.0.price', 'variants.0.compare_at_price']);
    }

    public function test_buy_x_get_y_offer_requires_known_target_variant_skus(): void
    {
        $seller = $this->seller();
        $store = $this->storeFor($seller);

        $response = $this->actingAs($seller, 'sanctum')
            ->postJson("/api/v1/stores/{$store->id}/products", $this->payload([
                'images' => [],
                'offer' => [
                    'type' => 'buy_x_get_y',
                    'status' => 'active',
                    'buy_qty' => 2,
                    'get_qty' => 1,
                    'allow_mix_buy_variants' => true,
                    'allow_mix_reward_variants' => false,
                    'buy_variant_skus' => ['MISSING-SKU'],
                    'reward_variant_skus' => ['SKU-001-RED-XL'],
                ],
            ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['offer.buy_variant_skus']);
    }

    public function test_buy_x_get_y_offer_returns_target_variant_ids(): void
    {
        $seller = $this->seller();
        $store = $this->storeFor($seller);

        $response = $this->actingAs($seller, 'sanctum')
            ->postJson("/api/v1/stores/{$store->id}/products", $this->payload([
                'images' => [],
                'variants' => [
                    [
                        'title' => 'Red / XL',
                        'option_summary' => 'Color: Red, Size: XL',
                        'sku' => 'SKU-001-RED-XL',
                        'barcode' => '123456789',
                        'original_price' => 110,
                        'currency' => 'EGP',
                        'sort_order' => 0,
                        'is_default' => true,
                        'is_active' => true,
                        'inventory_mode' => 'tracked',
                        'stock_qty' => 8,
                        'low_stock_threshold' => 2,
                        'allow_backorder' => false,
                        'attributes' => [],
                    ],
                    [
                        'title' => 'Blue / L',
                        'option_summary' => 'Color: Blue, Size: L',
                        'sku' => 'SKU-001-BLUE-L',
                        'barcode' => '987654321',
                        'original_price' => 115,
                        'currency' => 'EGP',
                        'sort_order' => 1,
                        'is_default' => false,
                        'is_active' => true,
                        'inventory_mode' => 'tracked',
                        'stock_qty' => 5,
                        'low_stock_threshold' => 1,
                        'allow_backorder' => false,
                        'attributes' => [],
                    ],
                ],
                'offer' => [
                    'type' => 'buy_x_get_y',
                    'status' => 'active',
                    'label' => 'Buy 2 get 1',
                    'buy_qty' => 2,
                    'get_qty' => 1,
                    'allow_mix_buy_variants' => true,
                    'allow_mix_reward_variants' => true,
                    'buy_variant_skus' => ['SKU-001-RED-XL', 'SKU-001-BLUE-L'],
                    'reward_variant_skus' => ['SKU-001-BLUE-L'],
                ],
            ]));

        $response->assertCreated()
            ->assertJsonPath('data.offer.type', 'buy_x_get_y')
            ->assertJsonCount(2, 'data.offer.buy_variant_ids')
            ->assertJsonCount(1, 'data.offer.reward_variant_ids');
    }

    public function test_tracked_inventory_requires_stock_quantity(): void
    {
        $seller = $this->seller();
        $store = $this->storeFor($seller);
        $category = Category::factory()->create();

        $response = $this->actingAs($seller, 'sanctum')
            ->postJson("/api/v1/stores/{$store->id}/products", $this->payload([
                'category_ids' => [$category->id],
                'images' => [],
                'variants' => [
                    [
                        'title' => 'Tracked Variant',
                        'option_summary' => 'Tracked stock',
                        'sku' => 'TRACKED-NO-STOCK',
                        'barcode' => '111222333',
                        'original_price' => 110,
                        'currency' => 'EGP',
                        'sort_order' => 0,
                        'is_default' => true,
                        'is_active' => true,
                        'inventory_mode' => 'tracked',
                        'stock_qty' => null,
                        'low_stock_threshold' => null,
                        'allow_backorder' => false,
                        'attributes' => [],
                    ]
                ],
            ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['variants.0.stock_qty']);
    }

    public function test_unlimited_inventory_is_always_in_stock(): void
    {
        $seller = $this->seller();
        $store = $this->storeFor($seller);
        $category = Category::factory()->create();

        $response = $this->actingAs($seller, 'sanctum')
            ->postJson("/api/v1/stores/{$store->id}/products", $this->payload([
                'category_ids' => [$category->id],
                'images' => [],
                'variants' => [
                    [
                        'title' => 'Unlimited Variant',
                        'option_summary' => 'Unlimited stock',
                        'sku' => 'UNLIMITED-STOCK',
                        'barcode' => '999888777',
                        'original_price' => 110,
                        'currency' => 'EGP',
                        'sort_order' => 0,
                        'is_default' => true,
                        'is_active' => true,
                        'inventory_mode' => 'unlimited',
                        'stock_qty' => null,
                        'low_stock_threshold' => null,
                        'allow_backorder' => false,
                        'attributes' => [],
                    ]
                ],
            ]));

        $response->assertCreated()
            ->assertJsonPath('data.variants.0.inventory_mode', 'unlimited')
            ->assertJsonPath('data.variants.0.stock_qty', null)
            ->assertJsonPath('data.variants.0.low_stock_threshold', null)
            ->assertJsonPath('data.variants.0.is_in_stock', true);
    }

    public function test_tracked_inventory_with_backorder_is_reported_in_stock(): void
    {
        $seller = $this->seller();
        $store = $this->storeFor($seller);
        $product = Product::factory()->create(['store_id' => $store->id]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'inventory_mode' => 'tracked',
            'stock_qty' => 0,
            'low_stock_threshold' => 3,
            'allow_backorder' => true,
        ]);

        $response = $this->actingAs($seller, 'sanctum')
            ->getJson("/api/v1/stores/{$store->id}/products/{$product->id}");

        $response->assertOk()
            ->assertJsonPath('data.variants.0.inventory_mode', 'tracked')
            ->assertJsonPath('data.variants.0.stock_qty', 0)
            ->assertJsonPath('data.variants.0.allow_backorder', true)
            ->assertJsonPath('data.variants.0.is_in_stock', true);
    }

    public function test_slug_uniqueness_per_store_is_enforced(): void
    {
        $seller = $this->seller();
        $store = $this->storeFor($seller);
        $category = Category::factory()->create();

        Product::factory()->create([
            'store_id' => $store->id,
            'slug' => 'example-product',
        ]);

        $response = $this->actingAs($seller, 'sanctum')
            ->postJson("/api/v1/stores/{$store->id}/products", $this->payload([
                'category_ids' => [$category->id],
                'images' => [],
            ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['slug']);
    }

    public function test_duplicate_titles_in_same_store_get_unique_generated_slug_and_sku_suffixes(): void
    {
        $seller = $this->seller();
        $store = $this->storeFor($seller);

        $firstResponse = $this->actingAs($seller, 'sanctum')
            ->postJson("/api/v1/stores/{$store->id}/products", $this->payload([
                'title' => 'Running Shoes',
                'slug' => null,
                'sku' => null,
                'images' => [],
                'variants' => [
                    [
                        'title' => 'Black / 42',
                        'option_summary' => 'Color: Black, Size: 42',
                        'sku' => null,
                        'barcode' => '123456789',
                        'original_price' => 110,
                        'currency' => 'EGP',
                        'sort_order' => 0,
                        'is_default' => true,
                        'is_active' => true,
                        'inventory_mode' => 'tracked',
                        'stock_qty' => 8,
                        'low_stock_threshold' => 2,
                        'allow_backorder' => false,
                        'attributes' => [
                            ['attribute_name' => 'color', 'attribute_value' => 'black', 'sort_order' => 0],
                            ['attribute_name' => 'size', 'attribute_value' => '42', 'sort_order' => 1],
                        ],
                    ],
                ],
            ]));

        $secondResponse = $this->actingAs($seller, 'sanctum')
            ->postJson("/api/v1/stores/{$store->id}/products", $this->payload([
                'title' => 'Running Shoes',
                'slug' => null,
                'sku' => null,
                'images' => [],
                'variants' => [
                    [
                        'title' => 'Blue / 43',
                        'option_summary' => 'Color: Blue, Size: 43',
                        'sku' => null,
                        'barcode' => '987654321',
                        'original_price' => 115,
                        'currency' => 'EGP',
                        'sort_order' => 0,
                        'is_default' => true,
                        'is_active' => true,
                        'inventory_mode' => 'tracked',
                        'stock_qty' => 5,
                        'low_stock_threshold' => 1,
                        'allow_backorder' => false,
                        'attributes' => [
                            ['attribute_name' => 'color', 'attribute_value' => 'blue', 'sort_order' => 0],
                            ['attribute_name' => 'size', 'attribute_value' => '43', 'sort_order' => 1],
                        ],
                    ],
                ],
            ]));

        $firstProductId = $firstResponse->json('data.id');
        $secondProductId = $secondResponse->json('data.id');

        $firstResponse->assertCreated()->assertJsonPath('data.slug', 'running-shoes');
        $secondResponse->assertCreated()->assertJsonPath('data.slug', 'running-shoes-2');

        $this->assertDatabaseHas('products', [
            'id' => $firstProductId,
            'slug' => 'running-shoes',
            'sku' => 'PRD-SHO-RUN',
        ]);
        $this->assertDatabaseHas('products', [
            'id' => $secondProductId,
            'slug' => 'running-shoes-2',
            'sku' => 'PRD-SHO-RUN-2',
        ]);
    }

    public function test_variant_sku_uniqueness_per_product_is_enforced(): void
    {
        $seller = $this->seller();
        $store = $this->storeFor($seller);
        $category = Category::factory()->create();
        $payload = $this->payload([
            'category_ids' => [$category->id],
            'images' => [],
            'variants' => [
                [
                    'title' => 'Red / XL',
                    'option_summary' => 'Color: Red, Size: XL',
                    'sku' => 'DUPLICATE-SKU',
                    'barcode' => '123456789',
                    'original_price' => 110,
                    'currency' => 'EGP',
                    'sort_order' => 0,
                    'is_default' => true,
                    'is_active' => true,
                    'attributes' => [],
                ],
                [
                    'title' => 'Blue / L',
                    'option_summary' => 'Color: Blue, Size: L',
                    'sku' => 'DUPLICATE-SKU',
                    'barcode' => '987654321',
                    'original_price' => 115,
                    'currency' => 'EGP',
                    'sort_order' => 1,
                    'is_default' => false,
                    'is_active' => true,
                    'attributes' => [],
                ],
            ],
        ]);

        $response = $this->actingAs($seller, 'sanctum')
            ->postJson("/api/v1/stores/{$store->id}/products", $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['variants']);
    }

    public function test_nested_store_product_route_requires_matching_store(): void
    {
        $seller = $this->seller();
        $store = $this->storeFor($seller);
        $anotherStore = $this->storeFor($seller);
        $product = Product::factory()->create(['store_id' => $anotherStore->id]);

        $response = $this->actingAs($seller, 'sanctum')
            ->getJson("/api/v1/stores/{$store->id}/products/{$product->id}");

        $response->assertNotFound();
    }

    public function test_seller_can_manage_variants_through_separate_endpoints(): void
    {
        $seller = $this->seller();
        $store = $this->storeFor($seller);
        $product = Product::factory()->create(['store_id' => $store->id]);

        $createResponse = $this->actingAs($seller, 'sanctum')
            ->postJson("/api/v1/stores/{$store->id}/products/{$product->id}/variants", [
                'title' => 'Blue / L',
                'option_summary' => 'Color: Blue, Size: L',
                'sku' => 'BLUE-L',
                'barcode' => '123456001',
                'original_price' => 140,
                'currency' => 'EGP',
                'is_default' => true,
                'is_active' => true,
                'inventory_mode' => 'tracked',
                'stock_qty' => 0,
                'low_stock_threshold' => 5,
                'allow_backorder' => true,
                'attributes' => [
                    ['attribute_name' => 'color', 'attribute_value' => 'blue', 'sort_order' => 0],
                    ['attribute_name' => 'size', 'attribute_value' => 'L', 'sort_order' => 1],
                ],
            ]);

        $variantId = $createResponse->json('data.id');

        $createResponse->assertCreated()
            ->assertJsonPath('data.sku', 'BLUE-L')
            ->assertJsonPath('data.is_default', true)
            ->assertJsonPath('data.original_price', '140.00')
            ->assertJsonPath('data.price', '130.00')
            ->assertJsonPath('data.compare_at_price', '140.00')
            ->assertJsonPath('data.inventory_mode', 'tracked')
            ->assertJsonPath('data.stock_qty', 0)
            ->assertJsonPath('data.low_stock_threshold', 5)
            ->assertJsonPath('data.allow_backorder', true)
            ->assertJsonPath('data.is_in_stock', true);

        $updateResponse = $this->actingAs($seller, 'sanctum')
            ->putJson("/api/v1/stores/{$store->id}/products/{$product->id}/variants/{$variantId}", [
                'title' => 'Blue / XL',
                'original_price' => 150,
                'is_active' => false,
                'stock_qty' => 4,
            ]);

        $updateResponse->assertOk()
            ->assertJsonPath('data.title', 'Blue / XL')
            ->assertJsonPath('data.is_active', false)
            ->assertJsonPath('data.original_price', '150.00')
            ->assertJsonPath('data.price', '140.00')
            ->assertJsonPath('data.compare_at_price', '150.00')
            ->assertJsonPath('data.stock_qty', 4);

        $this->assertDatabaseHas('product_variants', [
            'id' => $variantId,
            'title' => 'Blue / XL',
            'is_active' => false,
            'original_price' => 150,
            'price' => 140,
            'compare_at_price' => 150,
            'inventory_mode' => 'tracked',
            'stock_qty' => 4,
            'allow_backorder' => true,
        ]);
    }

    public function test_separate_variant_default_endpoint_behavior_unsets_previous_default(): void
    {
        $seller = $this->seller();
        $store = $this->storeFor($seller);
        $product = Product::factory()->create(['store_id' => $store->id]);
        $existingDefault = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'is_default' => true,
            'sku' => 'DEFAULT-ONE',
        ]);

        $response = $this->actingAs($seller, 'sanctum')
            ->postJson("/api/v1/stores/{$store->id}/products/{$product->id}/variants", [
                'title' => 'Second Variant',
                'sku' => 'DEFAULT-TWO',
                'original_price' => 125,
                'currency' => 'EGP',
                'is_default' => true,
            ]);

        $newVariantId = $response->json('data.id');

        $response->assertCreated()
            ->assertJsonPath('data.is_default', true);

        $this->assertDatabaseHas('product_variants', [
            'id' => $existingDefault->id,
            'is_default' => false,
        ]);
        $this->assertDatabaseHas('product_variants', [
            'id' => $newVariantId,
            'is_default' => true,
        ]);
    }

    public function test_seller_can_replace_variant_attributes_through_separate_endpoint(): void
    {
        $seller = $this->seller();
        $store = $this->storeFor($seller);
        $product = Product::factory()->create(['store_id' => $store->id]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'sku' => 'ATTR-ONE',
        ]);
        $variant->attributes()->create([
            'attribute_name' => 'color',
            'attribute_value' => 'red',
            'sort_order' => 0,
            'created_at' => now(),
        ]);

        $response = $this->actingAs($seller, 'sanctum')
            ->putJson("/api/v1/stores/{$store->id}/products/{$product->id}/variants/{$variant->id}/attributes", [
                'attributes' => [
                    ['attribute_name' => 'material', 'attribute_value' => 'cotton', 'sort_order' => 0],
                    ['attribute_name' => 'size', 'attribute_value' => 'XL', 'sort_order' => 1],
                ],
            ]);

        $response->assertOk()
            ->assertJsonCount(2, 'data.attributes');

        $this->assertDatabaseMissing('product_variant_attributes', [
            'variant_id' => $variant->id,
            'attribute_name' => 'color',
        ]);
        $this->assertDatabaseHas('product_variant_attributes', [
            'variant_id' => $variant->id,
            'attribute_name' => 'material',
        ]);
    }

    public function test_seller_can_manage_images_through_separate_endpoints(): void
    {
        $seller = $this->seller();
        $store = $this->storeFor($seller);
        $product = Product::factory()->create(['store_id' => $store->id]);

        $createResponse = $this->actingAs($seller, 'sanctum')
            ->post("/api/v1/stores/{$store->id}/products/{$product->id}/images", [
                'images' => [
                    [
                        'file' => UploadedFile::fake()->create('primary.jpg', 100, 'image/jpeg'),
                        'sort_order' => 0,
                        'is_primary' => true,
                    ],
                    [
                        'file' => UploadedFile::fake()->create('secondary.jpg', 100, 'image/jpeg'),
                        'sort_order' => 1,
                        'is_primary' => false,
                    ],
                ],
            ]);

        $firstImageId = $createResponse->json('data.0.id');
        $secondImageId = $createResponse->json('data.1.id');

        $createResponse->assertCreated()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.is_primary', true);

        $setPrimaryResponse = $this->actingAs($seller, 'sanctum')
            ->patchJson("/api/v1/stores/{$store->id}/products/{$product->id}/images/{$secondImageId}/primary");

        $setPrimaryResponse->assertOk()
            ->assertJsonPath('data.id', $secondImageId)
            ->assertJsonPath('data.is_primary', true);

        $reorderResponse = $this->actingAs($seller, 'sanctum')
            ->patchJson("/api/v1/stores/{$store->id}/products/{$product->id}/images/reorder", [
                'images' => [
                    ['id' => $firstImageId, 'sort_order' => 2],
                    ['id' => $secondImageId, 'sort_order' => 0],
                ],
            ]);

        $reorderResponse->assertOk()
            ->assertJsonPath('data.0.id', $secondImageId);

        $this->assertDatabaseHas('product_images', [
            'id' => $firstImageId,
            'is_primary' => false,
            'sort_order' => 2,
        ]);
        $this->assertDatabaseHas('product_images', [
            'id' => $secondImageId,
            'is_primary' => true,
            'sort_order' => 0,
        ]);
    }

    private function seller(): User
    {
        $seller = User::factory()->create();
        $seller->assignRole('seller');

        return $seller;
    }

    private function customer(): User
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        return $customer;
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }

    private function storeFor(User $user): Store
    {
        return Store::factory()->create([
            'owner_user_id' => $user->id,
        ]);
    }

    private function payload(array $overrides = []): array
    {
        $payload = [
            'title' => 'Example Product',
            'slug' => 'example-product',
            'short_description' => 'Short description',
            'description' => 'Long description',
            'currency' => 'EGP',
            'sku' => 'SKU-001',
            'is_featured' => false,
            'category_ids' => [],
            'images' => [
                [
                    'file' => UploadedFile::fake()->create('product.jpg', 100, 'image/jpeg'),
                    'sort_order' => 0,
                    'is_primary' => true,
                ],
            ],
            'variants' => [
                [
                    'title' => 'Red / XL',
                    'option_summary' => 'Color: Red, Size: XL',
                    'sku' => 'SKU-001-RED-XL',
                    'barcode' => '123456789',
                    'original_price' => 110,
                    'currency' => 'EGP',
                    'sort_order' => 0,
                    'is_default' => true,
                    'is_active' => true,
                    'inventory_mode' => 'tracked',
                    'stock_qty' => 8,
                    'low_stock_threshold' => 2,
                    'allow_backorder' => false,
                    'attributes' => [
                        [
                            'attribute_name' => 'color',
                            'attribute_value' => 'red',
                            'sort_order' => 0,
                        ],
                        [
                            'attribute_name' => 'size',
                            'attribute_value' => 'XL',
                            'sort_order' => 1,
                        ],
                    ],
                ],
            ],
            'offer' => [
                'type' => 'fixed',
                'status' => 'active',
                'label' => 'Launch discount',
                'claim_expiration_minutes' => 1440,
                'fixed_amount' => 15,
                'percentage_value' => null,
                'max_discount' => null,
                'buy_qty' => null,
                'get_qty' => null,
                'allow_mix_buy_variants' => false,
                'allow_mix_reward_variants' => false,
                'buy_variant_skus' => [],
                'reward_variant_skus' => [],
            ],
        ];

        return array_replace_recursive($payload, $overrides);
    }

    private function recordProductView(User $user, Product $product, ?\Illuminate\Support\Carbon $createdAt = null): void
    {
        $timestamp = $createdAt ?? now();

        $view = ProductView::query()->create([
            'product_id' => $product->id,
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
        ]);

        ProductView::query()->whereKey($view->id)->update([
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
    }

    private function recordProductLike(User $user, Product $product, ?\Illuminate\Support\Carbon $createdAt = null): void
    {
        $timestamp = $createdAt ?? now();

        $like = ProductLike::query()->create([
            'product_id' => $product->id,
            'user_id' => $user->id,
        ]);

        ProductLike::query()->whereKey($like->id)->update([
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
    }

    private function recordProductFavorite(User $user, Product $product, ?\Illuminate\Support\Carbon $createdAt = null): void
    {
        $timestamp = $createdAt ?? now();

        $favorite = ProductFavorite::query()->create([
            'product_id' => $product->id,
            'user_id' => $user->id,
        ]);

        ProductFavorite::query()->whereKey($favorite->id)->update([
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
    }

    private function recordProductComment(User $user, Product $product, ?\Illuminate\Support\Carbon $createdAt = null): void
    {
        $timestamp = $createdAt ?? now();

        $comment = ProductComment::query()->create([
            'product_id' => $product->id,
            'user_id' => $user->id,
            'rating' => 5,
            'body' => 'Helpful product review.',
        ]);

        ProductComment::query()->whereKey($comment->id)->update([
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
    }

    private function recordOfferClaim(User $user, Product $product, ?\Illuminate\Support\Carbon $createdAt = null): void
    {
        $timestamp = $createdAt ?? now();

        $claim = OfferClaim::query()->create([
            'user_id' => $user->id,
            'store_id' => $product->store_id,
            'product_id' => $product->id,
            'offer_id' => $product->offer->id,
            'status' => 'active',
            'claim_token' => 'CLM-' . \Illuminate\Support\Str::upper(\Illuminate\Support\Str::random(24)),
            'qr_code_token' => 'QR-' . \Illuminate\Support\Str::upper(\Illuminate\Support\Str::random(24)),
            'offer_snapshot' => [
                'product_title' => $product->title,
                'store_name' => $product->store->name,
            ],
            'expires_at' => now()->addDay(),
        ]);

        OfferClaim::query()->whereKey($claim->id)->update([
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
    }
}
