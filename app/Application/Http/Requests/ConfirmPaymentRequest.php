<?php

namespace App\Application\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'session_id' => ['required', 'uuid'],
        ];
    }

    public function messages(): array
    {
        return [
            'session_id.required' => __('validation.custom.session_id.required'),
            'session_id.uuid' => __('validation.custom.session_id.uuid'),
        ];
    }
}
