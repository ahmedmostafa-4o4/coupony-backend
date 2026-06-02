<?php

namespace App\Application\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RejectBannerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }
}
