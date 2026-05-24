<?php

namespace App\Domain\Product\Events;

use App\Domain\Product\Models\Product;
use App\Domain\Product\Models\ProductRevision;
use App\Domain\User\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProductRevisionSubmitted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Product $product,
        public ProductRevision $revision,
        public User $submittedBy
    ) {}
}
