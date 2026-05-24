<?php

namespace App\Domain\Store\Events;

use App\Domain\Store\Models\Store;
use App\Domain\User\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StoreFollowed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Store $store,
        public User $follower
    ) {}
}
