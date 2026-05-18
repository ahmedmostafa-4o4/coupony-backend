<?php

namespace App\Domain\Product\Actions;

use App\Domain\Product\Models\Product;
use App\Domain\Product\Repositories\ProductRepository;

class UpdateProductStatus
{
    public function __construct(private readonly ProductRepository $products) {}

    public function execute(Product $product, string $status): Product
    {
        return $this->products->updateStatus($product, $status);
    }
}
