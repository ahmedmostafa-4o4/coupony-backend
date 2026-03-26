<?php

namespace App\Application\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSocialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $social = $this->route('social');

        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('socials', 'name')->ignore($social?->id),
            ],
            'icon' => 'sometimes|required|file|mimes:png,jpg,jpeg,svg|max:2048',
        ];
    }
}
