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
            'document_type.required' => 'Document type is required.',
            'document_type.in' => 'Invalid document type.',
            'document.required' => 'Document file is required.',
            'document.mimes' => 'Document must be a PDF or image file.',
            'document.max' => 'Document size must not exceed 5MB.',
        ];
    }
}
