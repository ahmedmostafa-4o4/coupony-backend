<?php

namespace App\Domain\Product\Actions;

use App\Domain\Product\DTOs\ProductData;
use App\Domain\Product\Enums\ProductApprovalStatus;
use App\Domain\Product\Enums\ProductStatus;
use App\Domain\Product\Models\Product;
use App\Domain\Product\Repositories\ProductRepository;
use App\Domain\Product\Support\PrepareProductIdentifiers;
use App\Domain\Store\Models\Store;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\DB;

class CreateAdminProduct
{
    public function __construct(
        private readonly ProductRepository $products,
        private readonly ResolveVariantOfferPricing $pricing,
        private readonly PrepareProductIdentifiers $identifiers,
    ) {}

    public function execute(Store $store, ProductData $data, User $admin): Product
    {
        $storedPaths = [];

        try {
            return DB::transaction(function () use ($store, $data, $admin, &$storedPaths) {
                $data = $this->identifiers->forCreate($store, $data);
                $resolvedVariants = $this->pricing->resolve($data->variants(), $data->offer());
                $pricingSummary = $this->pricing->deriveProductPricingSummary($resolvedVariants);

                $product = $this->products->create($store, [
                    ...$data->attributes(),
                    ...$pricingSummary,
                    'status' => ProductStatus::ACTIVE,
                    'approval_status' => ProductApprovalStatus::APPROVED,
                    'published_revision_no' => 1,
                    'approved_at' => now(),
                    'approved_by' => $admin->id,
                    'rejected_at' => null,
                    'rejected_by' => null,
                    'rejection_reason' => null,
                ]);

                if ($data->hasCategoryIds()) {
                    $this->products->syncCategories($product, $data->categoryIds());
                }

                if ($data->hasImages()) {
                    $imageResult = $this->products->replaceImages($product, $data->images());
                    $storedPaths = $imageResult['stored'];
                }

                if ($data->hasVariants()) {
                    $this->products->replaceVariants($product, $resolvedVariants);
                }

                if ($data->hasOffer()) {
                    $this->products->syncOffer($product, $data->offer());
                }

                return $this->products->loadAdminProduct($product);
            });
        } catch (\Throwable $throwable) {
            $this->products->deleteFiles($storedPaths);

            throw $throwable;
        }
    }
}
