<?php

namespace App\Domain\Product\Events;

use App\Domain\Product\Models\OfferClaim;
use App\Domain\Product\Models\Product;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OfferClaimCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public OfferClaim $claim,
        public Product $product,
        public User $customer,
    ) {}
}
