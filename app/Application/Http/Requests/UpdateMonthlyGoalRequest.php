<?php

namespace App\Application\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMonthlyGoalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'goal' => ['required', 'integer', 'min:1'],
        ];
    }
}
