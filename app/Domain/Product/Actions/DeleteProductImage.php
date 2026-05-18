<?php

namespace App\Domain\Product\Actions;

use App\Domain\Product\Models\Product;
use App\Domain\Product\Models\ProductImage;
use App\Domain\Product\Repositories\ProductRepository;
use Illuminate\Support\Facades\DB;

class DeleteProductImage
{
    public function __construct(private readonly ProductRepository $products) {}

    public function execute(Product $product, ProductImage $image): bool
    {
        $path = $image->image_url;

        return DB::transaction(function () use ($product, $image, $path) {
            $deleted = $this->products->deleteImage($product, $image);

            DB::afterCommit(function () use ($path) {
                $this->products->deleteFiles([$path]);
            });

            return $deleted;
        });
    }
}
