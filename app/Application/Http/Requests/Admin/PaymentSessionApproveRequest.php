<?php

namespace App\Application\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class PaymentSessionApproveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payment_method' => ['required', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
