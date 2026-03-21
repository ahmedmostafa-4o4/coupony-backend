<?php

namespace App\Application\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
            // Basic store info
            'name' => ['required', 'string', 'max:255', 'unique:store_categories,name'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

}
