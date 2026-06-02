<?php

namespace App\Application\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminTravelBannerSelectableProductsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'min_review_score' => ['nullable', 'numeric', 'min:0', 'max:5'],
            'sort_by' => ['nullable', 'string', 'in:most_likes,most_saves,price_asc,price_desc,newest'],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => ['nullable', 'numeric', 'min:0'],
            'search' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
