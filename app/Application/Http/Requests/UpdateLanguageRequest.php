<?php

namespace App\Application\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLanguageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'language' => ['required', 'string', Rule::in(array_keys(config('localization.supported_locales', [])))],
        ];
    }

    public function attributes(): array
    {
        return [
            'language' => __('validation.attributes.language'),
        ];
    }
}
