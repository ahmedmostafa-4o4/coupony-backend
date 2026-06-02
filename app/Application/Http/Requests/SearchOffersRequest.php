<?php

namespace App\Application\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SearchOffersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'integer', 'exists:categories,id'],
            'sort_by' => ['nullable', 'string', 'in:popular,newest,price_high,price_low'],
            'min_rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => ['nullable', 'numeric', 'min:0'],
            'quick_filter' => ['nullable', 'string', 'in:all,newest,nearby,all_offers'],
            'page' => ['nullable', 'integer', 'min:1'],
            'page_size' => ['nullable', 'integer', 'min:1', 'max:100'],
            'lat' => ['nullable', 'numeric', 'between:-90,90', 'required_if:quick_filter,nearby'],
            'lng' => ['nullable', 'numeric', 'between:-180,180', 'required_if:quick_filter,nearby'],
        ];
    }
}
