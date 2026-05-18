<?php

namespace App\Domain\Store\Events;

use App\Domain\Store\Models\Store;
use App\Domain\User\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StoreOwnershipTransferred
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Store $store;

    public User $currentOwner;

    public User $newOwner;

    /**
     * Create a new class instance.
     */
    public function __construct(Store $store, User $currentOwner, User $newOwner)
    {
        $this->store = $store;
        $this->currentOwner = $currentOwner;
        $this->newOwner = $newOwner;
    }
}
