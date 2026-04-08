<?php

namespace App\Domain\Product\Actions;

use App\Domain\Product\DTOs\ProductData;
use App\Domain\Product\Models\Product;
use App\Domain\Product\Repositories\ProductRepository;
use Illuminate\Support\Facades\DB;

class UpdateProduct
{
    public function __construct(private readonly ProductRepository $products)
    {
    }

    public function execute(Product $product, ProductData $data): Product
    {
        $storedPaths = [];
        $deletedPaths = [];

        try {
            return DB::transaction(function () use ($product, $data, &$storedPaths, &$deletedPaths) {
                if ($data->attributes() !== []) {
                    $product = $this->products->update($product, $data->attributes());
                }

                if ($data->hasCategoryIds()) {
                    $this->products->syncCategories($product, $data->categoryIds());
                }

                if ($data->hasImages()) {
                    $imageResult = $this->products->replaceImages($product, $data->images());
                    $storedPaths = $imageResult['stored'];
                    $deletedPaths = $imageResult['deleted'];

                    DB::afterCommit(function () use ($deletedPaths) {
                        $this->products->deleteFiles($deletedPaths);
                    });
                }

                if ($data->hasVariants()) {
                    $this->products->replaceVariants($product, $data->variants());
                }

                return $this->products->loadSellerProduct($product);
            });
        } catch (\Throwable $throwable) {
            $this->products->deleteFiles($storedPaths);

            throw $throwable;
        }
    }
}
