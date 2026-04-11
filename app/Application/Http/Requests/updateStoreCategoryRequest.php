<?php

namespace App\Application\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class updateStoreCategoryRequest extends FormRequest
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
            'name_ar' => ['sometimes', 'string', 'max:255', Rule::unique('store_categories', 'name_ar')->ignore($this->category->id)],
            'name_en' => ['sometimes', 'string', 'max:255', Rule::unique('store_categories', 'name_en')->ignore($this->category->id)],
            'slug' => ['sometimes', 'nullable', 'string', 'max:255', Rule::unique('store_categories', 'slug')->ignore($this->category->id)],
            'icon' => ['sometimes', 'nullable', 'file', 'mimes:jpg,jpeg,png,webp,svg', 'max:2048'],
            'sort_order' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
