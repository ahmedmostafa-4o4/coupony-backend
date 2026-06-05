<?php

namespace App\Application\Http\Requests\Admin;

use App\Domain\Subscription\Enums\BillingCycle;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSubscriptionAssignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // authorization handled by middleware
    }

    public function rules(): array
    {
        return [
            'plan_id' => ['required', 'string', 'exists:subscription_plans,id'],
            'billing_cycle' => ['required', 'string', Rule::in(array_column(BillingCycle::cases(), 'value'))],
        ];
    }
}
