<?php

namespace App\Application\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductAnalyticsRequest extends FormRequest
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
            'period' => ['sometimes', 'string', 'in:all,today,last_7_days,last_30_days,this_month,this_year'],
            'start_date' => ['nullable', 'date_format:Y-m-d', 'required_with:end_date'],
            'end_date' => ['nullable', 'date_format:Y-m-d', 'required_with:start_date', 'after_or_equal:start_date'],
        ];
    }
}
