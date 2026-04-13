<?php

namespace App\Domain\Product\Actions;

use App\Domain\Product\DTOs\ProductData;
use App\Domain\Product\Models\Product;
use App\Domain\Product\Repositories\ProductRepository;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\DB;

class UpdateProduct
{
    public function __construct(
        private readonly ProductRepository $products,
        private readonly CreateOrUpdatePendingProductRevision $revisions,
    )
    {
    }

    public function execute(Product $product, ProductData $data, User $submittedBy): Product
    {
        $storedPaths = [];
        $deletedPaths = [];

        try {
            return DB::transaction(function () use ($product, $data, $submittedBy, &$storedPaths, &$deletedPaths) {
                if ($product->approval_status === \App\Domain\Product\Enums\ProductApprovalStatus::APPROVED) {
                    $this->revisions->execute($product, $data, $submittedBy);

                    return $this->products->loadSellerProduct($product->fresh());
                }

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
