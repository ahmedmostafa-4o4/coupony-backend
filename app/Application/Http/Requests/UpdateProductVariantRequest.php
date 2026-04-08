<?php

namespace App\Application\Http\Requests;

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
            'price' => ['nullable', 'numeric', 'min:0'],
            'compare_at_price' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['sometimes', 'required', 'string', 'size:3'],
            'sort_order' => ['nullable', 'integer'],
            'is_default' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (!$this->exists('compare_at_price') || $this->input('compare_at_price') === null) {
                return;
            }

            $price = $this->exists('price')
                ? $this->input('price')
                : $this->route('variant')->price;

            if ($price !== null && (float) $this->input('compare_at_price') < (float) $price) {
                $validator->errors()->add('compare_at_price', __('validation.custom.variants.compare_at_price'));
            }
        });
    }
}
