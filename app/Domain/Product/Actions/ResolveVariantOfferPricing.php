<?php

namespace App\Domain\Product\Actions;

use App\Domain\Product\Enums\ProductOfferType;
use InvalidArgumentException;

class ResolveVariantOfferPricing
{
    public function resolve(array $variants, array $offer): array
    {
        $offerType = $offer['type'] ?? null;

        if ($offerType === null) {
            throw new InvalidArgumentException('Offer type is required to resolve variant pricing.');
        }

        $this->validateOffer($offerType, $offer);

        return collect($variants)
            ->map(function (array $variant) use ($offerType, $offer) {
                $originalPrice = round((float) ($variant['original_price'] ?? 0), 2);

                if ($originalPrice < 0) {
                    throw new InvalidArgumentException('Original price must be greater than or equal to zero.');
                }

                [$price, $compareAtPrice] = $this->resolvePricePair($offerType, $originalPrice, $offer);

                return [
                    ...$variant,
                    'original_price' => $this->formatDecimal($originalPrice),
                    'price' => $this->formatDecimal($price),
                    'compare_at_price' => $compareAtPrice === null ? null : $this->formatDecimal($compareAtPrice),
                ];
            })
            ->values()
            ->all();
    }

    public function deriveProductPricingSummary(array $variants): array
    {
        if ($variants === []) {
            return [
                'base_price' => 0,
                'compare_at_price' => null,
            ];
        }

        $summaryVariant = collect($variants)
            ->sortBy([
                fn (array $variant) => ! ($variant['is_default'] ?? false),
                fn (array $variant) => (int) ($variant['sort_order'] ?? 0),
                fn (array $variant) => (string) ($variant['sku'] ?? ''),
            ])
            ->first();

        return [
            'base_price' => $summaryVariant['price'],
            'compare_at_price' => $summaryVariant['compare_at_price'],
        ];
    }

    private function validateOffer(string $offerType, array $offer): void
    {
        if ($offerType === ProductOfferType::FIXED->value) {
            $fixedAmount = (float) ($offer['fixed_amount'] ?? 0);

            if ($fixedAmount <= 0) {
                throw new InvalidArgumentException('Fixed amount must be greater than zero.');
            }
        }

        if ($offerType === ProductOfferType::PERCENTAGE->value) {
            $percentage = (float) ($offer['percentage_value'] ?? 0);

            if ($percentage <= 0 || $percentage > 100) {
                throw new InvalidArgumentException('Percentage value must be greater than zero and less than or equal to 100.');
            }
        }
    }

    private function resolvePricePair(string $offerType, float $originalPrice, array $offer): array
    {
        if ($offerType === ProductOfferType::FIXED->value) {
            $price = round($originalPrice - (float) $offer['fixed_amount'], 2);

            if ($price < 0) {
                throw new InvalidArgumentException('Resolved fixed-offer price cannot be negative.');
            }

            return [$price, $originalPrice];
        }

        if ($offerType === ProductOfferType::PERCENTAGE->value) {
            $discount = round($originalPrice * ((float) $offer['percentage_value'] / 100), 2);
            $price = round($originalPrice - $discount, 2);

            if ($price < 0) {
                throw new InvalidArgumentException('Resolved percentage-offer price cannot be negative.');
            }

            return [$price, $originalPrice];
        }

        if ($offerType === ProductOfferType::BUY_X_GET_Y->value) {
            return [$originalPrice, null];
        }

        throw new InvalidArgumentException('Unsupported offer type for pricing resolution.');
    }

    private function formatDecimal(float $value): string
    {
        return number_format($value, 2, '.', '');
    }
}
