<?php

namespace Tests\Unit\PonyAI;

use App\Domain\PonyAI\Support\GroundingValidator;
use App\Domain\Product\Models\Product;
use App\Domain\Product\Models\ProductOffer;
use App\Domain\Store\Models\Store;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GroundingValidatorTest extends TestCase
{
    use RefreshDatabase;

    private function product(): Product
    {
        $store = Store::factory()->create([
            'owner_user_id' => User::factory()->create()->id,
        ]);

        return Product::factory()->create(['store_id' => $store->id])->fresh(['offer']);
    }

    public function test_keeps_only_ids_that_exist_in_the_candidate_set(): void
    {
        $a = $this->product();
        $b = $this->product();

        [$grounded, $dropped] = $this->app->make(GroundingValidator::class)
            ->groundProducts(collect([$a, $b]), ['hallucinated-uuid', $b->id, 'another-fake']);

        $this->assertSame([$b->id], $grounded->pluck('id')->all());
        $this->assertSame(['hallucinated-uuid', 'another-fake'], $dropped);
    }

    public function test_returns_candidates_in_model_chosen_order(): void
    {
        $a = $this->product();
        $b = $this->product();
        $c = $this->product();

        [$grounded] = $this->app->make(GroundingValidator::class)
            ->groundProducts(collect([$a, $b, $c]), [$c->id, $a->id]);

        $this->assertSame([$c->id, $a->id], $grounded->pluck('id')->all());
    }

    public function test_empty_model_list_returns_full_candidate_collection(): void
    {
        $a = $this->product();
        $b = $this->product();

        [$grounded, $dropped] = $this->app->make(GroundingValidator::class)
            ->groundProducts(collect([$a, $b]), []);

        $this->assertSame([$a->id, $b->id], $grounded->pluck('id')->all());
        $this->assertSame([], $dropped);
    }

    public function test_empty_candidate_set_returns_empty_collection_and_records_drops(): void
    {
        [$grounded, $dropped] = $this->app->make(GroundingValidator::class)
            ->groundProducts(collect(), ['x', 'y']);

        $this->assertTrue($grounded->isEmpty());
        $this->assertSame(['x', 'y'], $dropped);
    }

    public function test_blank_or_non_string_ids_are_filtered_out(): void
    {
        $a = $this->product();

        [$grounded, $dropped] = $this->app->make(GroundingValidator::class)
            ->groundProducts(collect([$a]), [$a->id, '', '   ', 42, null, $a->id]);

        $this->assertSame([$a->id], $grounded->pluck('id')->all());
        $this->assertSame([], $dropped);
    }

    public function test_ground_offers_keeps_only_offer_ids_from_candidate_set(): void
    {
        $a = $this->product();
        $offerId = $a->offer->id;

        $kept = $this->app->make(GroundingValidator::class)
            ->groundOffers(collect([$a]), ['fake-offer-id', $offerId]);

        $this->assertSame([$offerId], $kept);
    }

    public function test_ground_offers_returns_empty_when_no_offers_loaded(): void
    {
        $a = $this->product();
        // Strip the offer relation so $product->relationLoaded('offer') === false from caller's perspective.
        $a->unsetRelation('offer');

        $kept = $this->app->make(GroundingValidator::class)
            ->groundOffers(collect([$a]), ['any']);

        $this->assertSame([], $kept);
    }
}
