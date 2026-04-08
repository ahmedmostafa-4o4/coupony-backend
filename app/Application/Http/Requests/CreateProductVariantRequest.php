<?php

namespace App\Application\Http\Requests;

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
            'price' => ['nullable', 'numeric', 'min:0'],
            'compare_at_price' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'sort_order' => ['nullable', 'integer'],
            'is_default' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'attributes' => ['nullable', 'array'],
            'attributes.*.attribute_name' => ['required', 'string', 'max:100'],
            'attributes.*.attribute_value' => ['required', 'string', 'max:255'],
            'attributes.*.sort_order' => ['nullable', 'integer'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (
                $this->filled('price')
                && $this->filled('compare_at_price')
                && (float) $this->input('compare_at_price') < (float) $this->input('price')
            ) {
                $validator->errors()->add('compare_at_price', __('validation.custom.variants.compare_at_price'));
            }

            $attributeNames = collect($this->input('attributes', []))
                ->pluck('attribute_name')
                ->filter(fn($value) => filled($value))
                ->map(fn($value) => mb_strtolower((string) $value))
                ->duplicates();

            if ($attributeNames->isNotEmpty()) {
                $validator->errors()->add('attributes', __('validation.custom.attributes.unique_name'));
            }
        });
    }
}
