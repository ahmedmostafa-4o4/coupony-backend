<?php

namespace App\Domain\Product\Events;

use App\Domain\Product\Models\OfferClaim;
use App\Domain\Store\Models\Store;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OfferClaimRedeemed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public OfferClaim $claim,
        public Store $store,
        public User $redeemedBy,
        public bool $userPointsAwarded,
        public bool $storePointsAwarded,
        public int $userPoints,
        public int $storePoints,
    ) {}
}
