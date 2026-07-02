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
            'file.required' => __('validation.custom.import_products.file_required'),
            'file.mimes' => __('validation.custom.import_products.file_mimes'),
            'file.max' => __('validation.custom.import_products.file_max'),
            'store_id.required' => __('validation.custom.import_products.store_required'),
            'store_id.exists' => __('validation.custom.import_products.store_exists'),
        ];
    }
}
