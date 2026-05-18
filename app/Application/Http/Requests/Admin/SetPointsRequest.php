<?php

namespace App\Application\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SetPointsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'points' => ['required', 'integer', 'min:0', 'max:1000000'],
            'reason' => ['required', 'string', 'max:100'],
            'note' => ['nullable', 'string', 'max:1000'],
            'meta' => ['nullable', 'array'],
        ];
    }
}
