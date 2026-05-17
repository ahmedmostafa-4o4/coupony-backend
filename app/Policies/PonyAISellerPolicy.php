<?php

namespace App\Policies;

use App\Domain\Store\Models\Store;
use App\Domain\User\Models\User;

class PonyAISellerPolicy
{
    /**
     * Pony AI seller chat may be opened by the store owner or any user attached
     * to the store via store_employees. Admins are intentionally NOT granted
     * access here - admin tooling already exists elsewhere, and we keep the
     * blast radius of "seller assistant data" strictly to the seller's circle.
     */
    public function chat(User $user, Store $store): bool
    {
        if ($store->owner_user_id === $user->id) {
            return true;
        }

        return $store->hasEmployee($user);
    }
}
