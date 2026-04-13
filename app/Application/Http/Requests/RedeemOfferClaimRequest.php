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
        ];
    }
}
