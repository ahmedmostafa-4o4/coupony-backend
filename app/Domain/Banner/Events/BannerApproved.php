<?php

namespace App\Domain\Banner\Events;

use App\Domain\Banner\Models\Banner;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BannerApproved
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Banner $banner
    ) {}
}
