<?php

namespace App\Application\Http\Requests;

use App\Domain\Product\Enums\ProductOfferType;
use App\Domain\Product\Models\Product;
use Illuminate\Foundation\Http\FormRequest;

class CreateOfferClaimRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'variant_ids' => ['nullable', 'array'],
            'variant_ids.*' => ['string', 'size:36'],
            'buy_variant_ids' => ['nullable', 'array'],
            'buy_variant_ids.*' => ['string', 'size:36'],
            'reward_variant_ids' => ['nullable', 'array'],
            'reward_variant_ids.*' => ['string', 'size:36'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            /** @var Product $product */
            $product = $this->route('product');
            $product->loadMissing(['variants', 'offer.targets']);

            $offer = $product->offer;
            if (! $offer) {
                return;
            }

            $offerType = $offer->type?->value ?? $offer->type;

            if ($offerType === ProductOfferType::BUY_X_GET_Y->value) {
                $buyVariantIds = $this->input('buy_variant_ids', []);
                $rewardVariantIds = $this->input('reward_variant_ids', []);

                if (count($buyVariantIds) !== (int) $offer->buy_qty) {
                    $validator->errors()->add('buy_variant_ids', 'The buy variant ids field must contain exactly the configured buy quantity.');
                }

                if (count($rewardVariantIds) !== (int) $offer->get_qty) {
                    $validator->errors()->add('reward_variant_ids', 'The reward variant ids field must contain exactly the configured reward quantity.');
                }

                return;
            }

            if ($product->variants->isNotEmpty() && count($this->input('variant_ids', [])) === 0) {
                $validator->errors()->add('variant_ids', 'The variant ids field is required when the product has variants.');
            }
        });
    }
}
