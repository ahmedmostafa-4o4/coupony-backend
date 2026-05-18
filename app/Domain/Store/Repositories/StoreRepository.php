<?php

namespace App\Domain\Store\Repositories;

use App\Domain\Store\Models\Store;
use Cache;
use DB;

class StoreRepository implements StoreRepositoryInterface
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    public function create(array $data): Store
    {
        return Store::create($data);
    }

    public function update(string $id, array $data): Store
    {
        return DB::transaction(function () use ($id, $data) {
            $store = Store::findOrFail($id);
            $store->update($data);
            Cache::forget("store.by_id.{$id}");

            return $store->fresh();
        });
    }

    public function delete(string $id): bool
    {
        return false;
    }

    public function findByName(string $name): ?Store
    {
        DB::transaction(function () use ($name) {
            $cacheKey = "store.by_name.{$name}";

            $store = Cache::get($cacheKey);
            if ($store !== null) {
                return $store;
            }

            $store = Store::where($name, 'name')->first();

            if ($store) {
                Cache::put($cacheKey, $store, now()->addHour());
            }

            return $store;
        });
    }

    public function find(string $id): ?Store
    {
        DB::transaction(function () use ($id) {
            $cacheKey = "store.by_id.{$id}";

            $store = Cache::get($cacheKey);
            if ($store !== null) {
                return $store;
            }

            $store = Store::findOrFail($id);

            if ($store) {
                Cache::put($cacheKey, $store, now()->addHour());
            }

            return $store;
        });

    }

    public function all(): array
    {
        $store = Store::all();

        return $store->toArray();
    }
}
