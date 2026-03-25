<?php

namespace App\Application\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VerifyPasswordResetOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => 'required|email|max:255',
            'code' => 'required|string|digits:6',
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => __('validation.custom.email.required'),
            'email.email' => __('validation.custom.email.email'),
            'code.required' => __('validation.custom.code.required'),
            'code.digits' => __('validation.custom.code.digits'),
        ];
    }
}
