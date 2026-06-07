<?php

namespace App\Application\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ImportProductsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:zip', 'max:51200'], // 50MB max
            'store_id' => ['required', 'uuid', 'exists:stores,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Please upload a ZIP file.',
            'file.mimes' => 'The file must be a ZIP archive.',
            'file.max' => 'The file must not exceed 50MB.',
            'store_id.required' => 'A store ID is required to link the imported products.',
            'store_id.exists' => 'The specified store does not exist.',
        ];
    }
}
