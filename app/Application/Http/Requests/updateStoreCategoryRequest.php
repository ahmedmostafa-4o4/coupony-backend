<?php

namespace App\Application\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
            // Basic store info
            'name' => 'sometimes|string|unique:store_categories,name,' . $this->category->id,
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

}
