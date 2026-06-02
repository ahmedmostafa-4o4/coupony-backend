<?php

namespace App\Application\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminUpdateTravelBannerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => ['sometimes', 'required', 'uuid', 'exists:products,id'],
            'image' => ['nullable', 'image', 'max:5120'],
            'cta_text' => ['sometimes', 'required', 'string', 'max:255'],
            'save_percent' => ['sometimes', 'required', 'string', 'max:255'],
            'priority' => ['nullable', 'integer', 'min:0'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after:start_date'],
            'is_active' => ['boolean'],
        ];
    }
}
