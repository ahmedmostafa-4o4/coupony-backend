<?php

namespace App\Domain\Product\Actions;

use App\Domain\Product\Models\Product;
use App\Domain\Product\Repositories\ProductRepository;
use Illuminate\Support\Facades\DB;

class DeleteProduct
{
    public function __construct(private readonly ProductRepository $products) {}

    public function execute(Product $product): bool
    {
        return DB::transaction(function () use ($product) {
            $product->variants()->get()->each->delete();

            return $this->products->delete($product);
        });
    }
}
