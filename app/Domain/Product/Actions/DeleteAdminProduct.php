<?php

namespace App\Domain\Product\Actions;

use App\Domain\Product\Models\Product;

class DeleteAdminProduct
{
    public function __construct(private readonly DeleteProduct $deleteProduct) {}

    public function execute(Product $product): bool
    {
        return $this->deleteProduct->execute($product);
    }
}
