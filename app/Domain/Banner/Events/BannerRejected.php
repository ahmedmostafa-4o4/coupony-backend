<?php

namespace App\Domain\Banner\Events;

use App\Domain\Banner\Models\Banner;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BannerRejected
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Banner $banner,
        public readonly string $reason
    ) {}
}
