<?php

namespace App\Application\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BannerShareRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'platform' => ['nullable', 'string', 'in:whatsapp,facebook,twitter,instagram,copy_link,other'],
        ];
    }
}
