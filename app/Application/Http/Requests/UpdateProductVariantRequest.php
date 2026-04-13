<?php

namespace App\Application\Http\Requests;

use App\Domain\Product\Enums\InventoryMode;
use App\Domain\Product\Models\Product;
use App\Domain\Product\Models\ProductVariant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductVariantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var Product $product */
        $product = $this->route('product');
        /** @var ProductVariant $variant */
        $variant = $this->route('variant');

        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'option_summary' => ['nullable', 'string', 'max:255'],
            'sku' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('product_variants', 'sku')
                    ->where(fn($query) => $query->where('product_id', $product->id))
                    ->ignore($variant->id),
            ],
            'barcode' => ['nullable', 'string', 'max:100'],
            'original_price' => ['sometimes', 'required', 'numeric', 'min:0'],
            'price' => ['prohibited'],
            'compare_at_price' => ['prohibited'],
            'currency' => ['sometimes', 'required', 'string', 'size:3'],
            'sort_order' => ['nullable', 'integer'],
            'is_default' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'inventory_mode' => ['nullable', Rule::in(InventoryMode::values())],
            'stock_qty' => ['nullable', 'integer', 'min:0'],
            'low_stock_threshold' => ['nullable', 'integer', 'min:0'],
            'allow_backorder' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $inventoryMode = $this->input(
                'inventory_mode',
                $this->route('variant')->inventory_mode?->value ?? $this->route('variant')->inventory_mode ?? InventoryMode::UNLIMITED->value
            );

            $stockQty = $this->exists('stock_qty')
                ? $this->input('stock_qty')
                : $this->route('variant')->stock_qty;

            $lowStockThreshold = $this->exists('low_stock_threshold')
                ? $this->input('low_stock_threshold')
                : $this->route('variant')->low_stock_threshold;

            if ($inventoryMode === InventoryMode::TRACKED->value && $stockQty === null) {
                $validator->errors()->add('stock_qty', 'The stock qty field is required when inventory mode is tracked.');
            }

            if ($inventoryMode === InventoryMode::UNLIMITED->value && $stockQty !== null) {
                $validator->errors()->add('stock_qty', 'The stock qty field must be empty when inventory mode is unlimited.');
            }

            if ($inventoryMode === InventoryMode::UNLIMITED->value && $lowStockThreshold !== null) {
                $validator->errors()->add('low_stock_threshold', 'The low stock threshold field must be empty when inventory mode is unlimited.');
            }
        });
    }
}
