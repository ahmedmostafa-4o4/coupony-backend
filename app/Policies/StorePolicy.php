<?php

namespace App\Policies;

use App\Domain\Store\Enums\StoreStatus;
use App\Domain\Store\Enums\StorePermission;
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

    public function manageSubscription(User $user, Store $store): bool
    {
        return $store->owner_user_id === $user->id;
    }

    public function accessClaims(User $user, Store $store): bool
    {
        return $this->isOwnerOrAdmin($user, $store)
            || $store->employeeHasAnyPermission($user, [
                StorePermission::CLAIMS_VIEW->value,
                StorePermission::CLAIMS_MANAGE->value,
            ]);
    }

    public function redeemClaims(User $user, Store $store): bool
    {
        return $this->isOwnerOrAdmin($user, $store)
            || $store->employeeHasAnyPermission($user, [
                StorePermission::CLAIMS_REDEEM->value,
                StorePermission::CLAIMS_MANAGE->value,
            ]);
    }

    public function manageInvitations(User $user, Store $store): bool
    {
        return $this->isOwnerOrAdmin($user, $store)
            || $store->employeeHasPermission($user, StorePermission::EMPLOYEES_INVITE->value);
    }

    public function manageEmployees(User $user, Store $store): bool
    {
        return $this->isOwnerOrAdmin($user, $store)
            || $store->employeeHasPermission($user, StorePermission::EMPLOYEES_MANAGE->value);
    }

    public function viewEmployees(User $user, Store $store): bool
    {
        return $this->isOwnerOrAdmin($user, $store)
            || $store->employeeHasAnyPermission($user, [
                StorePermission::EMPLOYEES_VIEW->value,
                StorePermission::EMPLOYEES_MANAGE->value,
            ]);
    }

    public function updateEmployees(User $user, Store $store): bool
    {
        return $this->isOwnerOrAdmin($user, $store)
            || $store->employeeHasAnyPermission($user, [
                StorePermission::EMPLOYEES_UPDATE->value,
                StorePermission::EMPLOYEES_MANAGE->value,
            ]);
    }

    public function removeEmployees(User $user, Store $store): bool
    {
        return $this->isOwnerOrAdmin($user, $store)
            || $store->employeeHasAnyPermission($user, [
                StorePermission::EMPLOYEES_REMOVE->value,
                StorePermission::EMPLOYEES_MANAGE->value,
            ]);
    }

    public function viewAnalytics(User $user, Store $store): bool
    {
        return $this->isOwnerOrAdmin($user, $store)
            || $store->employeeHasPermission($user, StorePermission::ANALYTICS_VIEW->value);
    }

    public function manageSettings(User $user, Store $store): bool
    {
        return $this->isOwnerOrAdmin($user, $store)
            || $store->employeeHasPermission($user, StorePermission::SETTINGS_MANAGE->value);
    }

    public function manageProducts(User $user, Store $store): bool
    {
        return $this->isOwnerOrAdmin($user, $store)
            || $store->employeeHasPermission($user, StorePermission::PRODUCTS_MANAGE->value);
    }

    public function manageBanners(User $user, Store $store): bool
    {
        return $this->isOwnerOrAdmin($user, $store)
            || $store->employeeHasAnyPermission($user, [
                StorePermission::OFFERS_MANAGE->value,
                StorePermission::PRODUCTS_MANAGE->value,
            ]);
    }

    public function manageOrders(User $user, Store $store): bool
    {
        return $this->isOwnerOrAdmin($user, $store)
            || $store->employeeHasPermission($user, StorePermission::ORDERS_MANAGE->value);
    }

    public function manageBranches(User $user, Store $store): bool
    {
        return $this->isOwnerOrAdmin($user, $store)
            || $store->employeeHasPermission($user, StorePermission::BRANCHES_MANAGE->value);
    }

    private function isOwnerOrAdmin(User $user, Store $store): bool
    {
        return $store->owner_user_id === $user->id
            || $user->hasRole(['admin', 'super_admin']);
    }
}
