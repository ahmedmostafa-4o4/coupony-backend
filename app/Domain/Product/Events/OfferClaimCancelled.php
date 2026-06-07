<?php

namespace App\Domain\Product\Events;

use App\Domain\Product\Models\OfferClaim;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OfferClaimCancelled
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly OfferClaim $claim
    ) {}
}
