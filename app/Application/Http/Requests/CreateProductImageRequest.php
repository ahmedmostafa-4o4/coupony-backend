<?php

namespace App\Application\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateProductImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'images' => ['required', 'array', 'min:1'],
            'images.*.file' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp'],
            'images.*.sort_order' => ['nullable', 'integer'],
            'images.*.is_primary' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $primaryCount = collect($this->input('images', []))
                ->filter(fn (array $image) => (bool) ($image['is_primary'] ?? false))
                ->count();

            if ($primaryCount > 1) {
                $validator->errors()->add('images', __('validation.custom.images.single_primary'));
            }
        });
    }
}
