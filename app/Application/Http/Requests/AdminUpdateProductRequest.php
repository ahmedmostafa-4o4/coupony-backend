<?php

namespace App\Application\Http\Requests;

use App\Domain\Product\Enums\InventoryMode;
use App\Domain\Product\Enums\ProductOfferStatus;
use App\Domain\Product\Enums\ProductOfferType;
use App\Domain\Product\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminUpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var Product $product */
        $product = $this->route('product');

        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
                Rule::unique('products', 'slug')
                    ->where(fn($query) => $query->where('store_id', $product->store_id))
                    ->ignore($product->id),
            ],
            'short_description' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string'],
            'currency' => ['sometimes', 'required', 'string', 'size:3'],
            'sku' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('products', 'sku')
                    ->where(fn($query) => $query->where('store_id', $product->store_id))
                    ->ignore($product->id),
            ],
            'is_featured' => ['sometimes', 'boolean'],
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['integer', 'exists:categories,id'],
            'images' => ['nullable', 'array'],
            'images.*.file' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp'],
            'images.*.sort_order' => ['nullable', 'integer'],
            'images.*.is_primary' => ['nullable', 'boolean'],
            'variants' => ['nullable', 'array'],
            'variants.*.title' => ['required', 'string', 'max:255'],
            'variants.*.option_summary' => ['nullable', 'string', 'max:255'],
            'variants.*.sku' => ['nullable', 'string', 'max:100'],
            'variants.*.barcode' => ['nullable', 'string', 'max:100'],
            'variants.*.original_price' => ['required_with:variants', 'numeric', 'min:0'],
            'variants.*.price' => ['prohibited'],
            'variants.*.compare_at_price' => ['prohibited'],
            'variants.*.currency' => ['required_with:variants', 'string', 'size:3'],
            'variants.*.sort_order' => ['nullable', 'integer'],
            'variants.*.is_default' => ['nullable', 'boolean'],
            'variants.*.is_active' => ['nullable', 'boolean'],
            'variants.*.inventory_mode' => ['nullable', Rule::in(InventoryMode::values())],
            'variants.*.stock_qty' => ['nullable', 'integer', 'min:0'],
            'variants.*.low_stock_threshold' => ['nullable', 'integer', 'min:0'],
            'variants.*.allow_backorder' => ['nullable', 'boolean'],
            'variants.*.attributes' => ['nullable', 'array'],
            'variants.*.attributes.*.attribute_name' => ['required', 'string', 'max:100'],
            'variants.*.attributes.*.attribute_value' => ['required', 'string', 'max:255'],
            'variants.*.attributes.*.sort_order' => ['nullable', 'integer'],
            'offer' => ['sometimes', 'required', 'array'],
            'offer.type' => ['required_with:offer', Rule::in(ProductOfferType::values())],
            'offer.status' => ['nullable', Rule::in(ProductOfferStatus::values())],
            'offer.label' => ['nullable', 'string', 'max:255'],
            'offer.starts_at' => ['nullable', 'date'],
            'offer.ends_at' => ['nullable', 'date', 'after:offer.starts_at'],
            'offer.claim_expiration_minutes' => ['nullable', 'integer', 'min:1'],
            'offer.fixed_amount' => ['nullable', 'numeric', 'gt:0'],
            'offer.percentage_value' => ['nullable', 'numeric', 'gt:0', 'lte:100'],
            'offer.max_discount' => ['nullable', 'numeric', 'gt:0'],
            'offer.buy_qty' => ['nullable', 'integer', 'min:1'],
            'offer.get_qty' => ['nullable', 'integer', 'min:1'],
            'offer.allow_mix_buy_variants' => ['nullable', 'boolean'],
            'offer.allow_mix_reward_variants' => ['nullable', 'boolean'],
            'offer.buy_variant_skus' => ['nullable', 'array'],
            'offer.buy_variant_skus.*' => ['string', 'max:100'],
            'offer.reward_variant_skus' => ['nullable', 'array'],
            'offer.reward_variant_skus.*' => ['string', 'max:100'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $variants = collect($this->input('variants', []) ?? []);

            if ($variants->isNotEmpty()) {
                $defaultCount = $variants->filter(fn(array $variant) => (bool) ($variant['is_default'] ?? false))->count();

                if ($defaultCount > 1) {
                    $validator->errors()->add('variants', __('validation.custom.variants.single_default'));
                }

                $duplicateSkus = $variants
                    ->pluck('sku')
                    ->filter(fn($sku) => filled($sku))
                    ->map(fn($sku) => mb_strtolower((string) $sku))
                    ->duplicates();

                if ($duplicateSkus->isNotEmpty()) {
                    $validator->errors()->add('variants', __('validation.custom.variants.unique_sku'));
                }

                foreach ($variants as $index => $variant) {
                    $inventoryMode = $variant['inventory_mode'] ?? InventoryMode::UNLIMITED->value;
                    $stockQty = $variant['stock_qty'] ?? null;

                    if ($inventoryMode === InventoryMode::TRACKED->value && $stockQty === null) {
                        $validator->errors()->add("variants.{$index}.stock_qty", 'The stock qty field is required when inventory mode is tracked.');
                    }

                    if ($inventoryMode === InventoryMode::UNLIMITED->value && array_key_exists('stock_qty', $variant) && $stockQty !== null) {
                        $validator->errors()->add("variants.{$index}.stock_qty", 'The stock qty field must be empty when inventory mode is unlimited.');
                    }

                    if ($inventoryMode === InventoryMode::UNLIMITED->value && array_key_exists('low_stock_threshold', $variant) && $variant['low_stock_threshold'] !== null) {
                        $validator->errors()->add("variants.{$index}.low_stock_threshold", 'The low stock threshold field must be empty when inventory mode is unlimited.');
                    }
                }
            }

            if ($this->exists('variants') && !$this->exists('offer')) {
                $validator->errors()->add('offer', 'The offer field is required when variants are replaced.');
            }

            if (!$this->exists('offer')) {
                return;
            }

            $offer = $this->input('offer', []);
            $offerType = $offer['type'] ?? null;
            $variantSkuSource = $this->exists('variants')
                ? $variants
                : $this->route('product')->variants()->get(['sku']);

            $variantSkus = collect($variantSkuSource)
                ->pluck('sku')
                ->filter(fn($sku) => filled($sku))
                ->map(fn($sku) => mb_strtolower((string) $sku))
                ->values();

            if ($offerType === ProductOfferType::FIXED->value && empty($offer['fixed_amount'])) {
                $validator->errors()->add('offer.fixed_amount', 'The fixed amount field is required when offer type is fixed.');
            }

            if ($offerType === ProductOfferType::PERCENTAGE->value && empty($offer['percentage_value'])) {
                $validator->errors()->add('offer.percentage_value', 'The percentage value field is required when offer type is percentage.');
            }

            if ($offerType === ProductOfferType::BUY_X_GET_Y->value) {
                if (empty($offer['buy_qty'])) {
                    $validator->errors()->add('offer.buy_qty', 'The buy qty field is required when offer type is buy_x_get_y.');
                }

                if (empty($offer['get_qty'])) {
                    $validator->errors()->add('offer.get_qty', 'The get qty field is required when offer type is buy_x_get_y.');
                }

                $buySkus = collect($offer['buy_variant_skus'] ?? [])
                    ->map(fn($sku) => mb_strtolower((string) $sku))
                    ->filter();
                $rewardSkus = collect($offer['reward_variant_skus'] ?? [])
                    ->map(fn($sku) => mb_strtolower((string) $sku))
                    ->filter();

                if ($buySkus->isEmpty()) {
                    $validator->errors()->add('offer.buy_variant_skus', 'The buy variant skus field is required when offer type is buy_x_get_y.');
                }

                if ($rewardSkus->isEmpty()) {
                    $validator->errors()->add('offer.reward_variant_skus', 'The reward variant skus field is required when offer type is buy_x_get_y.');
                }

                if ($buySkus->diff($variantSkus)->isNotEmpty()) {
                    $validator->errors()->add('offer.buy_variant_skus', 'The selected buy variant skus must exist in the product variants.');
                }

                if ($rewardSkus->diff($variantSkus)->isNotEmpty()) {
                    $validator->errors()->add('offer.reward_variant_skus', 'The selected reward variant skus must exist in the product variants.');
                }
            }
        });
    }
}
