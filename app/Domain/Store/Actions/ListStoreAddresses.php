<?php

namespace App\Domain\Store\Actions;

use App\Domain\Store\Models\Store;
use Illuminate\Database\Eloquent\Collection;

class ListStoreAddresses
{
    public function execute(Store $store): Collection
    {
        return $store->addresses()
            ->latest('addressables.created_at')
            ->get();
    }
}
