<?php

namespace App\Domain\User\Actions\Admin;

use App\Domain\Store\Models\Store;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DeleteUserAction
{
    public function execute(User $user): void
    {
        DB::transaction(function () use ($user) {
            $avatarPath = $this->resolvePublicStoragePath($user->profile?->avatar_url);
            $filePathsToDelete = $this->deleteOwnedStores($user);
            $filePathsToDelete = array_merge($filePathsToDelete, $this->detachOwnerAddresses(User::class, [$user->id]));

            $user->tokens()->delete();
            $user->sessions()->delete();
            Cache::forget("user.by_email.{$user->email}");
            Cache::forget("user.by_id.{$user->id}");
            $user->delete();

            if ($avatarPath) {
                $filePathsToDelete[] = $avatarPath;
            }

            DB::afterCommit(function () use ($filePathsToDelete) {
                $this->deleteFiles($filePathsToDelete);
            });
        });
    }

    private function deleteOwnedStores(User $user): array
    {
        $stores = $user->stores()
            ->with(['verifications:id,store_id,document_path', 'products.images:id,product_id,image_url'])
            ->get();

        if ($stores->isEmpty()) {
            return [];
        }

        $storeIds = $stores->pluck('id')->all();
        $filePaths = [];

        foreach ($stores as $store) {
            $filePaths = array_merge($filePaths, array_filter([
                $store->logo_url,
                $store->banner_url,
            ]));

            $filePaths = array_merge(
                $filePaths,
                $store->verifications->pluck('document_path')->filter()->values()->all(),
                $store->products->flatMap(fn ($product) => $product->images->pluck('image_url'))->filter()->values()->all()
            );
        }

        $filePaths = array_merge($filePaths, $this->detachOwnerAddresses(Store::class, $storeIds));

        foreach ($stores as $store) {
            $store->delete();
        }

        return $filePaths;
    }

    private function detachOwnerAddresses(string $ownerType, array $ownerIds): array
    {
        if ($ownerIds === []) {
            return [];
        }

        $addressIds = DB::table('addressables')
            ->where('owner_type', $ownerType)
            ->whereIn('owner_id', $ownerIds)
            ->pluck('address_id')
            ->unique()
            ->values()
            ->all();

        DB::table('addressables')
            ->where('owner_type', $ownerType)
            ->whereIn('owner_id', $ownerIds)
            ->delete();

        if ($addressIds === []) {
            return [];
        }

        $orphanedAddressIds = DB::table('addresses')
            ->whereIn('id', $addressIds)
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('addressables')
                    ->whereColumn('addressables.address_id', 'addresses.id');
            })
            ->pluck('id')
            ->all();

        if ($orphanedAddressIds !== []) {
            DB::table('addresses')->whereIn('id', $orphanedAddressIds)->delete();
        }

        return [];
    }

    private function resolvePublicStoragePath(?string $pathOrUrl): ?string
    {
        if (! $pathOrUrl) {
            return null;
        }

        $storagePrefix = rtrim(Storage::disk('public')->url(''), '/');

        if (str_starts_with($pathOrUrl, $storagePrefix.'/')) {
            return ltrim(substr($pathOrUrl, strlen($storagePrefix)), '/');
        }

        return $pathOrUrl;
    }

    private function deleteFiles(array $paths): void
    {
        foreach (array_unique(array_filter($paths)) as $path) {
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        }
    }
}
