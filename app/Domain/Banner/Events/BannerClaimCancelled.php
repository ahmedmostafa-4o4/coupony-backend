<?php

namespace App\Domain\Banner\Events;

use App\Domain\Banner\Models\BannerClaim;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BannerClaimCancelled
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly BannerClaim $claim
    ) {}
}
