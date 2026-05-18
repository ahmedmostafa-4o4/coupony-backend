<?php

namespace App\Domain\Product\Actions;

use App\Domain\Product\Models\ProductVariant;
use App\Domain\Product\Repositories\ProductRepository;
use Illuminate\Support\Facades\DB;

class ReplaceVariantAttributes
{
    public function __construct(private readonly ProductRepository $products) {}

    public function execute(ProductVariant $variant, array $attributes): ProductVariant
    {
        return DB::transaction(function () use ($variant, $attributes) {
            return $this->products->replaceVariantAttributes($variant, $attributes);
        });
    }
}
