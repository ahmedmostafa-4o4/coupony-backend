<?php

namespace Tests\Feature\Console;

use App\Domain\Product\Enums\OfferClaimStatus;
use App\Domain\Product\Models\OfferClaim;
use App\Domain\Product\Models\Product;
use App\Domain\Store\Models\Store;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpireOfferClaimsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_expires_only_overdue_active_claims_and_is_idempotent(): void
    {
        $user = User::factory()->create();
        $store = Store::factory()->create();
        $product = Product::factory()->create(['store_id' => $store->id]);
        $claimDefaults = [
            'user_id' => $user->id,
            'store_id' => $store->id,
            'product_id' => $product->id,
            'offer_id' => $product->offer()->firstOrFail()->id,
        ];

        $overdue = OfferClaim::factory()->create($claimDefaults + [
            'status' => OfferClaimStatus::ACTIVE,
            'expires_at' => now()->subMinute(),
        ]);
        $future = OfferClaim::factory()->create($claimDefaults + [
            'status' => OfferClaimStatus::ACTIVE,
            'expires_at' => now()->addMinute(),
        ]);
        $withoutExpiry = OfferClaim::factory()->create($claimDefaults + [
            'status' => OfferClaimStatus::ACTIVE,
            'expires_at' => null,
        ]);
        $redeemed = OfferClaim::factory()->create($claimDefaults + [
            'status' => OfferClaimStatus::REDEEMED,
            'expires_at' => now()->subMinute(),
        ]);
        $cancelled = OfferClaim::factory()->create($claimDefaults + [
            'status' => OfferClaimStatus::CANCELLED,
            'expires_at' => now()->subMinute(),
        ]);
        $expired = OfferClaim::factory()->create($claimDefaults + [
            'status' => OfferClaimStatus::EXPIRED,
            'expires_at' => now()->subMinute(),
        ]);

        $this->artisan('offer-claims:expire')
            ->expectsOutput('Expired 1 offer claim.')
            ->assertSuccessful();

        $this->assertSame(OfferClaimStatus::EXPIRED, $overdue->fresh()->status);
        $this->assertSame(OfferClaimStatus::ACTIVE, $future->fresh()->status);
        $this->assertSame(OfferClaimStatus::ACTIVE, $withoutExpiry->fresh()->status);
        $this->assertSame(OfferClaimStatus::REDEEMED, $redeemed->fresh()->status);
        $this->assertSame(OfferClaimStatus::CANCELLED, $cancelled->fresh()->status);
        $this->assertSame(OfferClaimStatus::EXPIRED, $expired->fresh()->status);

        $this->artisan('offer-claims:expire')
            ->expectsOutput('Expired 0 offer claims.')
            ->assertSuccessful();
    }
}
