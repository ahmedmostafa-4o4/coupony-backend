<?php

namespace App\Application\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SubscriptionPlanUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // authorization handled by middleware
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'price_monthly' => ['sometimes', 'required', 'numeric', 'min:0'],
            'price_yearly' => ['sometimes', 'required', 'numeric', 'min:0'],
            'currency' => ['sometimes', 'required', 'string', 'size:3'],
            'max_products' => ['nullable', 'integer', 'min:0'],
            'max_employees' => ['nullable', 'integer', 'min:0'],
            'max_branches' => ['nullable', 'integer', 'min:0'],
            'features' => ['nullable', 'array'],
            'grace_period_days' => ['sometimes', 'required', 'integer', 'min:0'],
            'degraded_period_days' => ['sometimes', 'required', 'integer', 'min:0'],
            'is_active' => ['boolean'],
            'sort_order' => ['integer', 'min:0'],
        ];
    }
}
