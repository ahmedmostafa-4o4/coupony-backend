<?php

namespace App\Application\Http\Requests\Concerns;

use App\Domain\Product\DTOs\ProductData;
use App\Domain\Product\Models\Product;
use App\Domain\Product\Support\PrepareProductIdentifiers;
use App\Domain\Store\Models\Store;
use Illuminate\Support\Collection;

trait PreparesProductVariantValidation
{
    protected function previewPreparedVariants(): Collection
    {
        $data = ProductData::fromRequest($this);

        if (!$data->hasVariants()) {
            return collect();
        }

        /** @var PrepareProductIdentifiers $prepare */
        $prepare = app(PrepareProductIdentifiers::class);

        if ($this->route('product') instanceof Product) {
            $prepared = $prepare->forUpdate($this->route('product'), $data);

            return collect($prepared->variants());
        }

        $store = $this->validationStore();

        if (!$store) {
            return collect($data->variants());
        }

        $prepared = $prepare->forCreate($store, $data);

        return collect($prepared->variants());
    }

    protected function preparedVariantSkuKeys(): Collection
    {
        return $this->previewPreparedVariants()
            ->pluck('sku')
            ->filter(fn($sku) => filled($sku))
            ->map(fn($sku) => mb_strtolower((string) $sku))
            ->values();
    }

    protected function validationStore(): ?Store
    {
        $routeStore = $this->route('store');

        if ($routeStore instanceof Store) {
            return $routeStore;
        }

        $storeId = $this->input('store_id');

        if (!is_string($storeId) || trim($storeId) === '') {
            return null;
        }

        return Store::query()->find($storeId);
    }
}
