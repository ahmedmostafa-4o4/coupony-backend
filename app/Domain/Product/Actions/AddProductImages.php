<?php

namespace App\Domain\Product\Actions;

use App\Domain\Product\Models\Product;
use App\Domain\Product\Repositories\ProductRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AddProductImages
{
    public function __construct(private readonly ProductRepository $products)
    {
    }

    public function execute(Product $product, array $images): Collection
    {
        $storedPaths = [];

        try {
            return DB::transaction(function () use ($product, $images, &$storedPaths) {
                $createdImages = $this->products->addImages($product, $images);
                $storedPaths = $createdImages->pluck('image_url')->filter()->values()->all();

                return $createdImages;
            });
        } catch (\Throwable $throwable) {
            $this->products->deleteFiles($storedPaths);

            throw $throwable;
        }
    }
}
