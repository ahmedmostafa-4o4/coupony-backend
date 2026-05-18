<?php

namespace App\Application\Http\Requests;

use App\Domain\Store\Enums\StorePermission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateStoreEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $store = $this->route('store');

        return $store && $this->user()->can('updateEmployees', $store);
    }

    public function rules(): array
    {
        return [
            'role' => [
                'sometimes',
                'string',
                Rule::in([
                    'store_manager',
                    'store_employee',
                    'branch_manager',
                    'cashier',
                    'inventory_manager',
                    'content_manager',
                    'support_agent',
                ]),
            ],
            'permissions' => ['sometimes', 'nullable', 'array'],
            'permissions.*' => ['string', Rule::in(StorePermission::values())],
            'address_id' => ['sometimes', 'nullable', 'integer', 'exists:addresses,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $store = $this->route('store');
            $addressId = $this->input('address_id');

            if (! $this->has('address_id') || $addressId === null || ! $store) {
                return;
            }

            if (! $store->addresses()->whereKey($addressId)->exists()) {
                $validator->errors()->add('address_id', __('validation.exists', ['attribute' => 'address_id']));
            }
        });
    }
}
