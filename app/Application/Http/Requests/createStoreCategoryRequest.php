<?php

namespace App\Application\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class createStoreCategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // You can customize this later to only allow authenticated users
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name_ar' => ['required', 'string', 'max:255', Rule::unique('store_categories', 'name_ar')],
            'name_en' => ['required', 'string', 'max:255', Rule::unique('store_categories', 'name_en')],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('store_categories', 'slug')],
            'icon' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,svg', 'max:2048'],
            'image_category' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,svg', 'max:4096'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
