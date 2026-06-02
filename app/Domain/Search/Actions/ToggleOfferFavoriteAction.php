<?php

namespace App\Domain\Search\Actions;

use App\Domain\Product\Models\Product;
use App\Domain\User\Models\User;

class ToggleOfferFavoriteAction
{
    /**
     * Toggles the favorite status of a product (offer) for the given user.
     * Returns true if it is now favorited, false otherwise.
     */
    public function execute(Product $product, User $user): bool
    {
        $existingFavorite = $user->favorites()->where('product_id', $product->id)->first();

        if ($existingFavorite) {
            $user->favorites()->where('product_id', $product->id)->delete();
            $product->decrement('favorites_count');
            return false;
        }

        $user->favorites()->create(['product_id' => $product->id]);
        $product->increment('favorites_count');
        
        return true;
    }
}
