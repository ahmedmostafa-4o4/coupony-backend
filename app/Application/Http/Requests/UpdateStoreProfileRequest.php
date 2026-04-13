<?php

namespace App\Application\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateStoreProfileRequest extends FormRequest
{
    private const ALLOWED_TOP_LEVEL_FIELDS = [
        'description',
        'email',
        'phone',
        'logo_url',
        'banner_url',
        'socials',
    ];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'description' => 'nullable|string|max:1000',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'logo_url' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'banner_url' => 'nullable|image|mimes:jpg,jpeg,png|max:5120',
            'socials' => 'nullable|array',
            'socials.*.social_id' => 'required_with:socials|exists:socials,id',
            'socials.*.link' => 'required_with:socials|url|max:500',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $unexpectedFields = collect(array_keys($this->all()))
                ->diff(self::ALLOWED_TOP_LEVEL_FIELDS);

            foreach ($unexpectedFields as $field) {
                $validator->errors()->add($field, 'This field is not allowed.');
            }

            foreach ($this->input('socials', []) as $index => $social) {
                if (!is_array($social)) {
                    continue;
                }

                $unexpectedNestedFields = collect(array_keys($social))
                    ->diff(['social_id', 'link']);

                foreach ($unexpectedNestedFields as $field) {
                    $validator->errors()->add("socials.{$index}.{$field}", 'This field is not allowed.');
                }
            }
        });
    }
}
