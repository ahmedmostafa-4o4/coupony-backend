<?php

namespace App\Application\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GoogleLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_token' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:255'],
            'role' => ['required', 'string', Rule::in(['customer', 'seller'])],
            'language' => ['nullable', 'string', Rule::in(array_keys(config('localization.supported_locales', [])))],
        ];
    }
}
