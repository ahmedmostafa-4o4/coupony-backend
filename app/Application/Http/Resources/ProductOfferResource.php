<?php

namespace App\Application\Http\Resources;

use App\Domain\Product\Enums\OfferClaimStatus;
use App\Domain\Product\Enums\ProductOfferTargetRole;
use App\Domain\Product\Models\OfferClaim;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductOfferResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $targets = $this->whenLoaded('targets', fn () => $this->targets);

        return [
            'id' => $this->id,
            'type' => $this->type?->value ?? $this->type,
            'status' => $this->status?->value ?? $this->status,
            'label' => $this->label,
            'terms' => $this->localizedTerms($request),
            'branch_only' => (bool) $this->branch_only,
            'starts_at' => $this->starts_at?->toIso8601String(),
            'ends_at' => $this->ends_at?->toIso8601String(),
            'claim_expiration_minutes' => $this->claim_expiration_minutes,
            'max_claims_per_user' => $this->max_claims_per_user,
            'max_total_claims' => $this->max_total_claims,
            'remaining_claims' => $this->remainingClaims($request),
            'remaining_total_claims' => $this->remainingTotalClaims(),
            'already_claimed' => $this->alreadyClaimed($request),
            'fixed_amount' => $this->fixed_amount,
            'percentage_value' => $this->percentage_value,
            'max_discount' => $this->max_discount,
            'buy_qty' => $this->buy_qty,
            'get_qty' => $this->get_qty,
            'allow_mix_buy_variants' => $this->allow_mix_buy_variants,
            'allow_mix_reward_variants' => $this->allow_mix_reward_variants,
            'buy_variant_ids' => $this->whenLoaded('targets', function () use ($targets) {
                return $targets
                    ->filter(fn ($target) => ($target->role?->value ?? $target->role) === ProductOfferTargetRole::BUY->value)
                    ->pluck('variant_id')
                    ->values()
                    ->all();
            }, []),
            'reward_variant_ids' => $this->whenLoaded('targets', function () use ($targets) {
                return $targets
                    ->filter(fn ($target) => ($target->role?->value ?? $target->role) === ProductOfferTargetRole::REWARD->value)
                    ->pluck('variant_id')
                    ->values()
                    ->all();
            }, []),
        ];
    }

    private function localizedTerms(Request $request): array
    {
        $preferred = str_starts_with(strtolower($request->getPreferredLanguage() ?? app()->getLocale()), 'ar')
            ? ($this->terms_ar ?? [])
            : ($this->terms_en ?? []);
        $fallback = str_starts_with(strtolower($request->getPreferredLanguage() ?? app()->getLocale()), 'ar')
            ? ($this->terms_en ?? [])
            : ($this->terms_ar ?? []);

        return array_values(array_filter($preferred ?: $fallback ?: [], fn ($term) => is_string($term) && $term !== ''));
    }

    private function alreadyClaimed(Request $request): bool
    {
        $user = $request->user();

        if (! $user) {
            return false;
        }

        return $this->claimsBaseQuery()
            ->where('user_id', $user->id)
            ->exists();
    }

    private function remainingClaims(Request $request): ?int
    {
        if ($this->max_claims_per_user === null || ! $request->user()) {
            return null;
        }

        $used = $this->claimsBaseQuery()
            ->where('user_id', $request->user()->id)
            ->count();

        return max(0, (int) $this->max_claims_per_user - $used);
    }

    private function remainingTotalClaims(): ?int
    {
        if ($this->max_total_claims === null) {
            return null;
        }

        return max(0, (int) $this->max_total_claims - $this->claimsBaseQuery()->count());
    }

    private function claimsBaseQuery()
    {
        return OfferClaim::query()
            ->where('offer_id', $this->id)
            ->where('status', '!=', OfferClaimStatus::CANCELLED->value);
    }
}
