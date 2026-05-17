<?php

namespace App\Domain\PonyAI\Support;

use App\Domain\Product\Models\Product;
use Illuminate\Support\Collection;

/**
 * Central enforcement of the "no invented products" rule.
 *
 * Every strategy (customer text chat, customer image search, seller chat) builds
 * a candidate set in SQL first, hands those candidates to Gemini, then asks
 * Gemini for a list of recommended product/offer ids. The model is allowed to
 * pick a subset of the candidates - or to refuse and return an empty list -
 * but it must never return an id we did not already verify in SQL.
 *
 * This class is the one place that enforces that rule, so we have one regression
 * surface to test rather than three.
 */
class GroundingValidator
{
    /**
     * @param  Collection<int, Product>  $candidates
     * @param  array<int, mixed>  $modelProductIds
     * @return array{0: Collection<int, Product>, 1: array<int, string>}
     *
     * Returns a tuple of [grounded products in model-chosen order, dropped ids].
     * If the model returned no ids the grounded collection is the candidate set
     * itself, in the order it was passed in - this gives the user *something*
     * useful even when the model is terse.
     */
    public function groundProducts(Collection $candidates, array $modelProductIds): array
    {
        if ($candidates->isEmpty()) {
            return [collect(), $this->cleanStringList($modelProductIds)];
        }

        $cleanIds = $this->cleanStringList($modelProductIds);

        if ($cleanIds === []) {
            return [$candidates->values(), []];
        }

        $byId = $candidates->keyBy('id');
        $kept = collect($cleanIds)
            ->map(fn(string $id) => $byId->get($id))
            ->filter()
            ->values();

        $dropped = array_values(array_diff(
            $cleanIds,
            $kept->pluck('id')->map(static fn($id): string => (string) $id)->all(),
        ));

        return [$kept, $dropped];
    }

    /**
     * @param  Collection<int, Product>  $candidates
     * @param  array<int, mixed>  $modelOfferIds
     * @return array<int, string>
     */
    public function groundOffers(Collection $candidates, array $modelOfferIds): array
    {
        $allowed = $candidates
            ->map(fn(Product $product) => $product->relationLoaded('offer') ? (string) ($product->offer?->id ?? '') : '')
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($allowed === []) {
            return [];
        }

        return array_values(array_intersect($this->cleanStringList($modelOfferIds), $allowed));
    }

    /**
     * @param  array<int, mixed>  $list
     * @return array<int, string>
     */
    private function cleanStringList(array $list): array
    {
        $out = [];

        foreach ($list as $item) {
            if (is_string($item) && trim($item) !== '') {
                $out[] = trim($item);
            }
        }

        return array_values(array_unique($out));
    }
}
