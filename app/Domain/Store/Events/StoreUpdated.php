<?php

namespace App\Domain\Store\Events;

use App\Domain\Store\Models\Store;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StoreUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Store $store,
        public User $user
    ) {}
}
