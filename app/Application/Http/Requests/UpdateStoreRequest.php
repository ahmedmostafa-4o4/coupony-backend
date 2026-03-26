<?php

namespace App\Application\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'phone' => 'sometimes|required|string|max:20',
            'email' => 'sometimes|required|email|max:255',
            'tax_id' => 'nullable|string|max:50',
            'subscription_tier' => 'nullable|string|in:free,basic,premium,enterprise',
            
            // Address
            'address' => 'nullable|array',
            'address.address_line1' => 'sometimes|required|string|max:255',
            'address.address_line2' => 'nullable|string|max:255',
            'address.city' => 'sometimes|required|string|max:100',
            'address.state' => 'nullable|string|max:100',
            'address.postal_code' => 'nullable|string|max:20',
            'address.country' => 'nullable|string|max:100',
            'address.latitude' => 'sometimes|numeric|between:-90,90',
            'address.longitude' => 'sometimes|numeric|between:-180,180',
            
            // Categories
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'exists:store_categories,id',
            
            // Files
            'logo' => 'nullable|file|image|mimes:jpg,jpeg,png|max:2048',
            'banner' => 'nullable|file|image|mimes:jpg,jpeg,png|max:5120',

            // Socials
            'socials' => 'nullable|array|min:1',
            'socials.*' => 'array',
            'socials.*.social_id' => 'required|integer|exists:socials,id',
            'socials.*.link' => 'required|string|url',
        ];
    }
}
