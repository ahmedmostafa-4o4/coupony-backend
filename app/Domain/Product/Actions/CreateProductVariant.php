<?php

namespace App\Domain\Product\Actions;

use App\Domain\Product\Models\Product;
use App\Domain\Product\Models\ProductVariant;
use App\Domain\Product\Repositories\ProductRepository;
use Illuminate\Support\Facades\DB;

class CreateProductVariant
{
    public function __construct(private readonly ProductRepository $products)
    {
    }

    public function execute(Product $product, array $attributes): ProductVariant
    {
        return DB::transaction(function () use ($product, $attributes) {
            return $this->products->createVariant($product, $attributes);
        });
    }
}
