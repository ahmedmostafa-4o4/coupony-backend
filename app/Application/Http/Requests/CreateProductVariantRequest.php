<?php

namespace App\Application\Http\Requests;

use App\Domain\Product\Enums\InventoryMode;
use App\Domain\Product\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateProductVariantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var Product $product */
        $product = $this->route('product');

        return [
            'title' => ['required', 'string', 'max:255'],
            'option_summary' => ['nullable', 'string', 'max:255'],
            'sku' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('product_variants', 'sku')->where(
                    fn($query) => $query->where('product_id', $product->id)
                ),
            ],
            'barcode' => ['nullable', 'string', 'max:100'],
            'original_price' => ['required', 'numeric', 'min:0'],
            'price' => ['prohibited'],
            'compare_at_price' => ['prohibited'],
            'currency' => ['required', 'string', 'size:3'],
            'sort_order' => ['nullable', 'integer'],
            'is_default' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'inventory_mode' => ['nullable', Rule::in(InventoryMode::values())],
            'stock_qty' => ['nullable', 'integer', 'min:0'],
            'low_stock_threshold' => ['nullable', 'integer', 'min:0'],
            'allow_backorder' => ['nullable', 'boolean'],
            'attributes' => ['nullable', 'array'],
            'attributes.*.attribute_name' => ['required', 'string', 'max:100'],
            'attributes.*.attribute_value' => ['required', 'string', 'max:255'],
            'attributes.*.sort_order' => ['nullable', 'integer'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $attributeNames = collect($this->input('attributes', []))
                ->pluck('attribute_name')
                ->filter(fn($value) => filled($value))
                ->map(fn($value) => mb_strtolower((string) $value))
                ->duplicates();

            if ($attributeNames->isNotEmpty()) {
                $validator->errors()->add('attributes', __('validation.custom.attributes.unique_name'));
            }

            $inventoryMode = $this->input('inventory_mode', InventoryMode::UNLIMITED->value);
            $stockQty = $this->input('stock_qty');

            if ($inventoryMode === InventoryMode::TRACKED->value && $stockQty === null) {
                $validator->errors()->add('stock_qty', 'The stock qty field is required when inventory mode is tracked.');
            }

            if ($inventoryMode === InventoryMode::UNLIMITED->value && $this->exists('stock_qty') && $stockQty !== null) {
                $validator->errors()->add('stock_qty', 'The stock qty field must be empty when inventory mode is unlimited.');
            }

            if (
                $inventoryMode === InventoryMode::UNLIMITED->value
                && $this->exists('low_stock_threshold')
                && $this->input('low_stock_threshold') !== null
            ) {
                $validator->errors()->add('low_stock_threshold', 'The low stock threshold field must be empty when inventory mode is unlimited.');
            }
        });
    }
}
