<?php

namespace App\Application\Http\Requests;

use App\Domain\Store\Enums\StorePermission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SendInvitationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $store = $this->route('store');
        return $store && $this->user()->can('manageInvitations', $store);
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'address_id' => ['nullable', 'integer', 'exists:addresses,id'],
            'role' => ['required', 'string', Rule::in(['store_manager', 'store_employee'])],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::in(StorePermission::values())],
            'message' => ['nullable', 'string', 'max:500'],
        ];
    }
}
