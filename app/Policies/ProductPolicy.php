<?php

namespace App\Policies;

use App\Domain\Product\Models\Product;
use App\Domain\Store\Models\Store;
use App\Domain\User\Models\User;

class ProductPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->hasAnyRole(['admin', 'super_admin']) ? true : null;
    }

    public function viewAny(User $user, Store $store): bool
    {
        return $store->owner_user_id === $user->id;
    }

    public function create(User $user, Store $store): bool
    {
        return $store->owner_user_id === $user->id;
    }

    public function view(User $user, Product $product): bool
    {
        return $product->store?->owner_user_id === $user->id;
    }

    public function update(User $user, Product $product): bool
    {
        return $product->store?->owner_user_id === $user->id;
    }

    public function delete(User $user, Product $product): bool
    {
        return $product->store?->owner_user_id === $user->id;
    }

    public function updateStatus(User $user, Product $product): bool
    {
        return $product->store?->owner_user_id === $user->id;
    }
}
