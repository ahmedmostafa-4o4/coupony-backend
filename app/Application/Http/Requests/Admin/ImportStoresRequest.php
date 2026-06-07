<?php

namespace App\Application\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ImportStoresRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:zip', 'max:51200'], // 50MB max
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Please upload a ZIP file.',
            'file.mimes' => 'The file must be a ZIP archive.',
            'file.max' => 'The file must not exceed 50MB.',
        ];
    }
}
