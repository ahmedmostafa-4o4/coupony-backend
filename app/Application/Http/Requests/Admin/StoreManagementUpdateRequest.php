<?php

namespace App\Application\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreManagementUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string|max:1000',
            'phone' => 'sometimes|string|max:20',
            'email' => 'sometimes|email|max:255',
            'tax_id' => 'nullable|string|max:50',
            'subscription_tier' => 'nullable|string|in:free,basic,premium,enterprise',
            'commission_rate' => 'nullable|numeric|between:0,1',
        ];
    }
}
