<?php

namespace App\Domain\Product\Actions;

use App\Domain\Product\Models\Product;
use App\Domain\Product\Repositories\ProductRepository;
use App\Domain\User\Models\User;

class UnlikeProduct
{
    public function __construct(private readonly ProductRepository $products)
    {
    }

    public function execute(Product $product, User $user): void
    {
        $this->products->unlike($product, $user);
    }
}
