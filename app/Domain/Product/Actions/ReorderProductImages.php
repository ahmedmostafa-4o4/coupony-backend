<?php

namespace App\Domain\Product\Actions;

use App\Domain\Product\Models\Product;
use App\Domain\Product\Repositories\ProductRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReorderProductImages
{
    public function __construct(private readonly ProductRepository $products) {}

    public function execute(Product $product, array $images): Collection
    {
        return DB::transaction(function () use ($product, $images) {
            return $this->products->reorderImages($product, $images);
        });
    }
}
