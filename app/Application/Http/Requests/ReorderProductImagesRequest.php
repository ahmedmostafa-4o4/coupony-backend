<?php

namespace App\Application\Http\Requests;

use App\Domain\Product\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReorderProductImagesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var Product $product */
        $product = $this->route('product');
        $imageIds = $product->images()->pluck('id')->all();

        return [
            'images' => ['required', 'array', 'min:1'],
            'images.*.id' => ['required', 'integer', Rule::in($imageIds)],
            'images.*.sort_order' => ['required', 'integer'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $providedIds = collect($this->input('images', []))->pluck('id');

            if ($providedIds->count() !== $providedIds->unique()->count()) {
                $validator->errors()->add('images', __('validation.custom.images.unique_ids'));
            }
        });
    }
}
