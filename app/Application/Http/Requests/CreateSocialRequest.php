<?php

namespace App\Application\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateSocialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:socials,name',
            'icon' => 'required|file|mimes:png,jpg,jpeg,svg|max:2048',
        ];
    }
}
