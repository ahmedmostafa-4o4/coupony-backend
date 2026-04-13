<?php

namespace App\Domain\Product\Actions;

use App\Domain\Product\DTOs\ProductData;
use App\Domain\Product\Models\Product;
use App\Domain\Product\Repositories\ProductRepository;
use App\Domain\Store\Models\Store;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\DB;

class CreateProduct
{
    public function __construct(
        private readonly ProductRepository $products,
        private readonly CreateOrUpdatePendingProductRevision $revisions,
    )
    {
    }

    public function execute(Store $store, ProductData $data, User $submittedBy): Product
    {
        $storedPaths = [];

        try {
            return DB::transaction(function () use ($store, $data, $submittedBy, &$storedPaths) {
                $product = $this->products->create($store, $data->attributes());

                if ($data->hasCategoryIds()) {
                    $this->products->syncCategories($product, $data->categoryIds());
                }

                if ($data->hasImages()) {
                    $imageResult = $this->products->replaceImages($product, $data->images());
                    $storedPaths = $imageResult['stored'];
                }

                if ($data->hasVariants()) {
                    $this->products->replaceVariants($product, $data->variants());
                }

                if ($data->hasOffer()) {
                    $this->products->syncOffer($product, $data->offer());
                }

                $this->revisions->execute($product, $data, $submittedBy);

                return $this->products->loadSellerProduct($product);
            });
        } catch (\Throwable $throwable) {
            $this->products->deleteFiles($storedPaths);

            throw $throwable;
        }
    }
}
