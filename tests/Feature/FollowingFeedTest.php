<?php

namespace Tests\Feature;

use App\Domain\Product\Models\Product;
use App\Domain\Store\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FollowingFeedTest extends TestCase
{
    use RefreshDatabase;

    public function test_feed_offer_id_is_the_product_id_for_product_details(): void
    {
        $store = Store::factory()->active()->create();
        $product = Product::factory()->active()->approved()->create(['store_id' => $store->id]);
        $offer = $product->offer;

        $response = $this->getJson('/api/v1/customer/home/following-feed');

        $response->assertOk()
            ->assertJsonPath('data.items.0.offer.id', $product->id);

        $this->assertNotSame($offer->id, $response->json('data.items.0.offer.id'));
    }
}
