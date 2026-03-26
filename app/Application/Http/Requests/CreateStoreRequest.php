<?php

namespace App\Application\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'phone' => 'required|string|max:20',
            'tax_id' => 'nullable|string|max:50',
            'subscription_tier' => 'nullable|string|in:free,basic,premium,enterprise',

            // Address
            'address_line1' => 'required_without:latitude,longitude|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'city' => 'required_without:latitude,longitude|string|max:100',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'label' => 'nullable|string|max:50',

            // Categories
            'categories' => 'required|array|min:1',
            'categories.*' => 'exists:store_categories,id',

            // Files
            'logo_url' => 'nullable|file|image|mimes:jpg,jpeg,png|max:2048',
            'banner_url' => 'nullable|file|image|mimes:jpg,jpeg,png|max:5120',

            // Socials
            'socials' => [
                'array',
                'nullable',
                'min:1',
            ],

            'socials.*' => [
                'array',
            ],

            'socials.*.social_id' => [
                'required',
                'integer',
                'exists:socials,id',
            ],

            'socials.*.link' => [
                'required',
                'string',
                'url',
            ],

            // Verification documents
            'verification_docs.commercial_register' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'verification_docs.tax_card' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'verification_docs.id_card_front' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'verification_docs.id_card_back' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => __('validation.custom.store_name.required'),
            'categories.required' => __('validation.custom.categories.required'),
            // 'verification_docs.*.required' => __('validation.custom.verification_docs.required'),

        ];
    }
}
