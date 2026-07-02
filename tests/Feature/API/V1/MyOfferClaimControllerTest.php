<?php

namespace Tests\Feature\API\V1;

use App\Domain\Product\Enums\OfferClaimStatus;
use App\Domain\Product\Models\Category;
use App\Domain\Product\Models\OfferClaim;
use App\Domain\Product\Models\Product;
use App\Domain\Product\Models\ProductImage;
use App\Domain\Product\Models\ProductOffer;
use App\Domain\Store\Models\Store;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MyOfferClaimControllerTest extends TestCase
{
    use RefreshDatabase;

    private function createClaim(User $user, ?Store $store = null, ?Product $product = null, string $status = 'active', ?string $token = null): OfferClaim
    {
        $token = $token ?? Str::random(10);
        $store = $store ?? Store::factory()->create();
        $product = $product ?? clone Product::factory()->create(['store_id' => $store->id]);

        $offer = ProductOffer::where('product_id', $product->id)->first();
        if (! $offer) {
            $offer = ProductOffer::factory()->create(['product_id' => $product->id]);
        }

        return OfferClaim::forceCreate([
            'id' => Str::uuid()->toString(),
            'user_id' => $user->id,
            'store_id' => $store->id,
            'product_id' => $product->id,
            'offer_id' => $offer->id,
            'status' => $status,
            'claim_token' => $token,
            'qr_code_token' => Str::random(10),
            'offer_snapshot' => '[]',
            'expires_at' => now()->addDays(7),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    #[Test]
    public function it_can_list_my_offer_claims(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $claim1 = $this->createClaim($user, null, null, 'active', 'TOKEN123');
        $this->createClaim($otherUser);

        $response = $this->actingAs($user)->getJson('/api/v1/me/offer-claims');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id', 'status', 'claim_token',
                    ],
                ],
                'meta',
            ])
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $claim1->id);
    }

    #[Test]
    public function it_returns_legacy_display_fallbacks_and_redeemed_offer_usage(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $store = Store::factory()->create();
        $product = clone Product::factory()->create(['store_id' => $store->id, 'title' => 'Legacy Product']);
        ProductImage::query()->create([
            'product_id' => $product->id,
            'image_url' => 'products/legacy.jpg',
            'is_primary' => true,
        ]);

        $claim = $this->createClaim($user, $store, $product);
        $redeemedClaim = $this->createClaim($otherUser, $store, $product, OfferClaimStatus::REDEEMED->value);
        $redeemedClaim->update(['offer_id' => $claim->offer_id]);
        $this->createClaim($otherUser, $store, $product, OfferClaimStatus::CANCELLED->value)
            ->update(['offer_id' => $claim->offer_id]);

        $response = $this->actingAs($user)->getJson('/api/v1/me/offer-claims');

        $response->assertOk()
            ->assertJsonPath('data.0.customer.id', $user->id)
            ->assertJsonPath('data.0.customer.name', $user->full_name)
            ->assertJsonPath('data.0.product.id', $product->id)
            ->assertJsonPath('data.0.product.title', 'Legacy Product')
            ->assertJsonPath('data.0.usage_count', 1);

        $this->assertStringContainsString('/storage/products/legacy.jpg', $response->json('data.0.product.image_url'));
    }

    #[Test]
    public function it_can_filter_claims_by_status(): void
    {
        $user = User::factory()->create();

        $this->createClaim($user, null, null, 'active');
        $this->createClaim($user, null, null, 'redeemed');

        $response = $this->actingAs($user)->getJson('/api/v1/me/offer-claims?status=active');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'active');
    }

    #[Test]
    public function it_returns_overdue_claims_in_the_expired_filter_after_synchronization(): void
    {
        $user = User::factory()->create();
        $claim = $this->createClaim($user);
        $claim->update(['expires_at' => now()->subMinute()]);

        $this->artisan('offer-claims:expire')->assertSuccessful();

        $this->actingAs($user)
            ->getJson('/api/v1/me/offer-claims?status=expired')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $claim->id)
            ->assertJsonPath('data.0.status', OfferClaimStatus::EXPIRED->value)
            ->assertJsonPath('data.0.is_expired', true);
    }

    #[Test]
    public function it_can_search_claims_by_product_title(): void
    {
        $user = User::factory()->create();

        $store = Store::factory()->create();
        $product1 = clone Product::factory()->create(['store_id' => $store->id, 'title' => 'Awesome Headset']);
        $product2 = clone Product::factory()->create(['store_id' => $store->id, 'title' => 'Keyboard']);

        $claim1 = $this->createClaim($user, $store, $product1);
        $this->createClaim($user, $store, $product2);

        $response = $this->actingAs($user)->getJson('/api/v1/me/offer-claims?search=Awesome');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.product_id', $claim1->product_id);
    }

    #[Test]
    public function it_can_filter_claims_by_category(): void
    {
        $user = User::factory()->create();

        $category = Category::factory()->create();

        $store = Store::factory()->create();
        $product1 = clone Product::factory()->create(['store_id' => $store->id]);
        $product1->categories()->attach($category->id);

        $product2 = clone Product::factory()->create(['store_id' => $store->id]);

        $claim1 = $this->createClaim($user, $store, $product1);
        $this->createClaim($user, $store, $product2);

        $response = $this->actingAs($user)->getJson('/api/v1/me/offer-claims?category='.$category->id);

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.product_id', $claim1->product_id);
    }

    #[Test]
    public function it_can_filter_claims_by_subcategory(): void
    {
        $user = User::factory()->create();
        $parent = Category::factory()->create(['slug' => 'food']);
        $subcategory = Category::factory()->create(['parent_id' => $parent->id, 'slug' => 'pizza']);
        $store = Store::factory()->create();
        $product1 = clone Product::factory()->create(['store_id' => $store->id]);
        $product1->categories()->attach($subcategory->id);
        $product2 = clone Product::factory()->create(['store_id' => $store->id]);

        $claim1 = $this->createClaim($user, $store, $product1);
        $this->createClaim($user, $store, $product2);

        $response = $this->actingAs($user)->getJson('/api/v1/me/offer-claims?subcategory='.$subcategory->id);

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $claim1->id);
    }

    #[Test]
    public function it_can_filter_claims_by_category_slug_or_parent_slug(): void
    {
        $user = User::factory()->create();
        $parent = Category::factory()->create([
            'name' => 'Restaurants',
            'name_en' => 'Restaurants',
            'slug' => 'restaurants',
        ]);
        $subcategory = Category::factory()->create([
            'name' => 'Burgers',
            'name_en' => 'Burgers',
            'parent_id' => $parent->id,
            'slug' => 'burgers',
        ]);
        $parent->update(['slug' => 'restaurants']);
        $subcategory->update(['slug' => 'burgers']);
        $store = Store::factory()->create();
        $product1 = clone Product::factory()->create(['store_id' => $store->id]);
        $product1->categories()->attach($subcategory->id);
        $product2 = clone Product::factory()->create(['store_id' => $store->id]);

        $claim1 = $this->createClaim($user, $store, $product1);
        $this->createClaim($user, $store, $product2);

        $parentResponse = $this->actingAs($user)->getJson('/api/v1/me/offer-claims?category_slug=restaurants');
        $childResponse = $this->actingAs($user)->getJson('/api/v1/me/offer-claims?category_slug=burgers');

        $childResponse->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $claim1->id);

        $parentResponse->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $claim1->id);
    }

    #[Test]
    public function it_can_sort_claims_by_supported_sort_options(): void
    {
        $user = User::factory()->create();
        $older = $this->createClaim($user, null, null, 'active');
        $older->forceFill([
            'offer_snapshot' => ['offer' => ['fixed_amount' => 5]],
            'expires_at' => now()->addDays(3),
            'created_at' => now()->subDays(2),
        ])->save();

        $newerHigherDiscount = $this->createClaim($user, null, null, 'active');
        $newerHigherDiscount->forceFill([
            'offer_snapshot' => ['offer' => ['fixed_amount' => 50]],
            'expires_at' => now()->addDays(5),
            'created_at' => now()->subDay(),
        ])->save();

        $expiresSoon = $this->createClaim($user, null, null, 'active');
        $expiresSoon->forceFill([
            'offer_snapshot' => ['offer' => ['fixed_amount' => 10]],
            'expires_at' => now()->addHour(),
            'created_at' => now(),
        ])->save();

        $this->actingAs($user)->getJson('/api/v1/me/offer-claims?sort_by=newest')
            ->assertOk()
            ->assertJsonPath('data.0.id', $expiresSoon->id);

        $this->actingAs($user)->getJson('/api/v1/me/offer-claims?sort_by=expires_soon')
            ->assertOk()
            ->assertJsonPath('data.0.id', $expiresSoon->id);

        $this->actingAs($user)->getJson('/api/v1/me/offer-claims?sort_by=status_then_discount')
            ->assertOk()
            ->assertJsonPath('data.0.id', $newerHigherDiscount->id);
    }

    #[Test]
    public function it_can_show_a_specific_claim(): void
    {
        $user = User::factory()->create();
        $claim = $this->createClaim($user);

        $response = $this->actingAs($user)->getJson("/api/v1/me/offer-claims/{$claim->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $claim->id);
    }

    #[Test]
    public function it_returns_404_if_claim_does_not_belong_to_user(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $claim = $this->createClaim($otherUser);

        $response = $this->actingAs($user)->getJson("/api/v1/me/offer-claims/{$claim->id}");

        $response->assertNotFound();
    }
}
