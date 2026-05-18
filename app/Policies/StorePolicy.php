<?php

namespace App\Policies;

use App\Domain\Store\Enums\StoreStatus;
use App\Domain\Store\Models\Store;
use App\Domain\User\Models\User;

class StorePolicy
{
    /**
     * Determine if the user can view the store.
     */
    public function view(User $user, Store $store): bool
    {
        return $store->owner_user_id === $user->id
            || $user->hasRole(['admin', 'super_admin']);
    }

    public function viewPoints(User $user, Store $store): bool
    {
        return $store->owner_user_id === $user->id
            || $store->hasEmployee($user)
            || $user->hasRole(['admin', 'super_admin']);
    }

    /**
     * Determine if the user can update the store.
     */
    public function update(User $user, Store $store): bool
    {
        // Only owner can update
        if ($store->owner_user_id !== $user->id) {
            return false;
        }

        // Cannot update approved/active stores
        return $store->status !== StoreStatus::ACTIVE;
    }

    /**
     * Determine if the user can update the store profile.
     */
    public function updateProfile(User $user, Store $store): bool
    {
        if ($store->owner_user_id !== $user->id) {
            return false;
        }

        return ! in_array($store->status, [
            StoreStatus::SUSPENDED,
            StoreStatus::CLOSED,
        ], true);
    }

    /**
     * Determine if the user can update verification documents.
     */
    public function updateVerificationDocuments(User $user, Store $store): bool
    {
        // Only owner can update
        if ($store->owner_user_id !== $user->id) {
            return false;
        }

        // Cannot update documents for approved stores
        return $store->status !== StoreStatus::ACTIVE;
    }

    /**
     * Determine if the user can delete the store.
     */
    public function delete(User $user, Store $store): bool
    {
        return $store->owner_user_id === $user->id
            || $user->hasRole(['admin', 'super_admin']);
    }

    public function manageAddresses(User $user, Store $store): bool
    {
        return $store->owner_user_id === $user->id;
    }

    public function accessClaims(User $user, Store $store): bool
    {
        return $user->hasRole('store_employee')
            && $store->hasEmployee($user);
    }

    public function manageInvitations(User $user, Store $store): bool
    {
        return $store->owner_user_id === $user->id;
    }

    public function manageEmployees(User $user, Store $store): bool
    {
        return $store->owner_user_id === $user->id;
    }
}
