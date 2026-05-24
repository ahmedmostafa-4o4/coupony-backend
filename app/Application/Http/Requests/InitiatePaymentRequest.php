<?php

namespace App\Application\Http\Requests;

use App\Domain\Subscription\Enums\BillingCycle;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InitiatePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'plan_id' => [
                'required',
                'string',
                Rule::exists('subscription_plans', 'id')->where('is_active', true),
            ],
            'billing_cycle' => [
                'required',
                'string',
                Rule::in(array_column(BillingCycle::cases(), 'value')),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'plan_id.required' => __('validation.custom.plan_id.required'),
            'plan_id.exists' => __('validation.custom.plan_id.exists'),
            'billing_cycle.required' => __('validation.custom.billing_cycle.required'),
            'billing_cycle.in' => __('validation.custom.billing_cycle.in'),
        ];
    }
}
