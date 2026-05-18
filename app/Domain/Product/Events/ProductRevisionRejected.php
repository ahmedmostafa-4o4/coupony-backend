<?php

namespace App\Domain\Product\Events;

use App\Domain\Product\Models\ProductRevision;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProductRevisionRejected
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public ProductRevision $revision,
        public User $admin,
        public string $reason,
    ) {}
}
