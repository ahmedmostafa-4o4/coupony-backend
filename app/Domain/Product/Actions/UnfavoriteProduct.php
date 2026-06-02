<?php

namespace App\Domain\Product\Actions;

use App\Domain\Product\Models\Product;
use App\Domain\Product\Repositories\ProductRepository;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\DB;

class UnfavoriteProduct
{
    public function __construct(private readonly ProductRepository $products) {}

    public function execute(Product $product, User $user): void
    {
        $this->products->unfavorite($product, $user);

        // Atomically decrement favorites_count, preventing negative values
        DB::table('products')
            ->where('id', $product->id)
            ->update(['favorites_count' => DB::raw('CASE WHEN favorites_count > 0 THEN favorites_count - 1 ELSE 0 END')]);
    }
}
