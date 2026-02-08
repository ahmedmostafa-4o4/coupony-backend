<?php

namespace App\Application\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class createStoreRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            //addresses
            'address_line1' => ['required_without:latitude,longitude', 'string', 'max:255'],
            'address_line2' => ['nullable', 'string', 'max:255'],
            'city' => ['required_without:latitude,longitude', 'string', 'max:100'],
            'latitude' => ['required_without:address_line1,city', 'nullable', 'numeric'],
            'longitude' => ['required_without:address_line1,city', 'nullable', 'numeric'],
            'label' => ['nullable', 'string', 'max:50'],
            // Optional fields
            'categories' => ['required', 'array', 'min:1'],
            'categories.*' => ['exists:store_categories,id'],
            // Verification docs
            'verification_docs' => ['required', 'array'],
            'verification_docs.commercial_register' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'verification_docs.tax_card' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'verification_docs.id_card_front' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'verification_docs.id_card_back' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ];
    }

    /**
     * Custom attribute names for cleaner error messages
     */
    public function attributes(): array
    {
        return [
            'name' => 'Store Name',
            'verification_docs.commercial_register' => 'Commercial Register Document',
            'verification_docs.tax_card' => 'Tax Card Document',
            'verification_docs.id_card' => 'ID Card Document',
        ];
    }
}
