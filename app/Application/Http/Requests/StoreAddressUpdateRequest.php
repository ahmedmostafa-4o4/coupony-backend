<?php

namespace App\Application\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAddressUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['sometimes', 'nullable', 'string', 'max:100'],
            'last_name' => ['sometimes', 'nullable', 'string', 'max:100'],
            'company' => ['sometimes', 'nullable', 'string', 'max:255'],
            'address_line1' => ['sometimes', 'string', 'max:255'],
            'address_line2' => ['sometimes', 'nullable', 'string', 'max:255'],
            'city' => ['sometimes', 'string', 'max:100'],
            'state_province' => ['sometimes', 'nullable', 'string', 'max:100'],
            'postal_code' => ['sometimes', 'nullable', 'string', 'max:20'],
            'country_code' => ['sometimes', 'nullable', 'string', 'size:2'],
            'phone_number' => ['sometimes', 'nullable', 'string', 'max:20'],
            'latitude' => ['sometimes', 'nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['sometimes', 'nullable', 'numeric', 'between:-180,180'],
            'delivery_instructions' => ['sometimes', 'nullable', 'string'],
            'label' => ['sometimes', 'nullable', 'string', 'max:50'],
            'is_default_shipping' => ['sometimes', 'boolean'],
            'is_default_billing' => ['sometimes', 'boolean'],
        ];
    }
}
