<?php

namespace App\Domain\Product\Actions;

use App\Domain\Product\Models\Product;
use App\Domain\Product\Models\ProductVariant;
use App\Domain\Product\Repositories\ProductRepository;
use Illuminate\Support\Facades\DB;

class DeleteProductVariant
{
    public function __construct(private readonly ProductRepository $products) {}

    public function execute(Product $product, ProductVariant $variant): bool
    {
        return DB::transaction(function () use ($product, $variant) {
            return $this->products->deleteVariant($product, $variant);
        });
    }
}
