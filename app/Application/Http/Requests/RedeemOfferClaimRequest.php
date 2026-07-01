<?php

namespace App\Application\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RedeemOfferClaimRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'qr_code_token' => ['required', 'string', 'max:100'],
            'revenue_amount' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99', 'required_with:currency'],
            'currency' => ['nullable', 'string', 'regex:/^[A-Z]{3}$/', 'required_with:revenue_amount'],
        ];
    }
}
