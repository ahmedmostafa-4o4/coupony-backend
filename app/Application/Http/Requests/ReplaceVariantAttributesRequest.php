<?php

namespace App\Application\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReplaceVariantAttributesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'attributes' => ['required', 'array'],
            'attributes.*.attribute_name' => ['required', 'string', 'max:100'],
            'attributes.*.attribute_value' => ['required', 'string', 'max:255'],
            'attributes.*.sort_order' => ['nullable', 'integer'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $duplicates = collect($this->input('attributes', []))
                ->pluck('attribute_name')
                ->filter(fn ($value) => filled($value))
                ->map(fn ($value) => mb_strtolower((string) $value))
                ->duplicates();

            if ($duplicates->isNotEmpty()) {
                $validator->errors()->add('attributes', __('validation.custom.attributes.unique_name'));
            }
        });
    }
}
