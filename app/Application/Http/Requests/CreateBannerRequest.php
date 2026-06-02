<?php

namespace App\Application\Http\Requests;

use App\Application\Http\Requests\Concerns\ValidatesBannerStorePayload;
use App\Domain\Store\Models\Store;
use Illuminate\Foundation\Http\FormRequest;

class CreateBannerRequest extends FormRequest
{
    use ValidatesBannerStorePayload;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'image' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'discount_label' => ['required', 'string', 'max:100'],
            'date_range' => ['nullable', 'string', 'max:100'],
            'cta_label' => ['required', 'string', 'max:100'],
            'terms_of_use' => ['required', 'string'],
            'end_time' => ['required', 'date', 'after:now'],
            'offer_ids' => ['required', 'array', 'min:1'],
            'offer_ids.*' => ['required', 'uuid', 'distinct', 'exists:product_offers,id'],
            'address_ids' => ['required', 'array', 'min:1'],
            'address_ids.*' => ['required', 'integer', 'distinct', 'exists:addresses,id'],
            'min_transaction' => ['prohibited'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            /** @var Store $store */
            $store = $this->route('store');
            $this->validateBannerStorePayload($validator, $store, true, true);
        });
    }
}
