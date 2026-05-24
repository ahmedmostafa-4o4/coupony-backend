<?php

namespace App\Application\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SellerDashboardRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (! $this->has('period')) {
            $this->merge([
                'period' => 'all',
            ]);
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'period' => ['sometimes', 'string', 'in:all,today,last_7_days,this_month,this_year'],
        ];
    }
}
