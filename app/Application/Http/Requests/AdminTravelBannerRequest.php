<?php

namespace App\Application\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminTravelBannerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => ['required', 'uuid', 'exists:products,id'],
            'image' => ['required', 'image', 'max:5120'], // Max 5MB
            'cta_text' => ['required', 'string', 'max:255'],
            'save_percent' => ['required', 'string', 'max:255'],
            'priority' => ['nullable', 'integer', 'min:0'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after:start_date'],
            'is_active' => ['boolean'],
        ];
    }
}
