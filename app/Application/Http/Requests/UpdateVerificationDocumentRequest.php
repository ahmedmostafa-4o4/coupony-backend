<?php

namespace App\Application\Http\Requests;

use App\Domain\Store\Enums\VerificationDocumentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVerificationDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'document_type' => [
                'required',
                'string',
                Rule::in(VerificationDocumentType::values()),
            ],
            'document' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ];
    }

    public function messages(): array
    {
        return [
            'document_type.required' => __('validation.custom.document_type.required'),
            'document_type.in' => __('validation.custom.document_type.in'),
            'document.required' => __('validation.custom.document.required'),
            'document.mimes' => __('validation.custom.document.mimes'),
            'document.max' => __('validation.custom.document.max'),
        ];
    }
}
