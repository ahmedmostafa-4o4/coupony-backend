<?php

namespace App\Domain\Product\Events;

use App\Domain\Product\Models\Product;
use App\Domain\Product\Models\ProductRevision;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProductRevisionApproved
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Product $product,
        public ProductRevision $revision,
        public User $admin,
    ) {
    }
}
