<?php

namespace App\Domain\Product\Actions;

use App\Domain\Product\Models\Product;
use App\Domain\Product\Models\ProductImage;
use App\Domain\Product\Repositories\ProductRepository;
use Illuminate\Support\Facades\DB;

class SetPrimaryProductImage
{
    public function __construct(private readonly ProductRepository $products) {}

    public function execute(Product $product, ProductImage $image): ProductImage
    {
        return DB::transaction(function () use ($product, $image) {
            return $this->products->setPrimaryImage($product, $image);
        });
    }
}
