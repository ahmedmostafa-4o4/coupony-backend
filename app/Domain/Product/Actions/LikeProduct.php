<?php

namespace App\Domain\Product\Actions;

use App\Domain\Product\Models\Product;
use App\Domain\Product\Models\ProductLike;
use App\Domain\Product\Repositories\ProductRepository;
use App\Domain\User\Models\User;

class LikeProduct
{
    public function __construct(private readonly ProductRepository $products)
    {
    }

    public function execute(Product $product, User $user): ProductLike
    {
        return $this->products->like($product, $user);
    }
}
