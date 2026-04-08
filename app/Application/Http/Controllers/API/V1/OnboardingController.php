<?php

namespace App\Application\Http\Controllers\API\V1;

use App\Application\Http\Controllers\Controller;
use App\Domain\User\Enums\BestOfferTimeCategory;
use App\Domain\User\Enums\BudgetCategory;
use App\Domain\User\Enums\CustomerReachMethodEnum;
use App\Domain\User\Enums\InterestingOfferCategory;
use App\Domain\User\Enums\PriceCategory;
use App\Domain\User\Enums\ShoppingStyleCategory;
use App\Domain\User\Enums\TargetAudienceCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class OnboardingController extends Controller
{
    public function customer(Request $request): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        $onboarding = DB::table('interests')
            ->where('user_id', $request->user()->id)
            ->first();

        return $this->localizedJson([
            'success' => true,
            'data' => [
                'interesting_offers' => $this->decodeJsonArray($onboarding?->interesting_offers),
                'shopping_style' => $this->decodeJsonArray($onboarding?->shopping_style),
                'budget' => $onboarding?->budget,
            ],
            'is_onboarding_completed' => $onboarding !== null,
        ]);
    }

    public function storeCustomer(Request $request): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        $data = $request->validate([
            'interesting_offers' => ['array'],
            'interesting_offers.*' => ['string', Rule::in(InterestingOfferCategory::values())],
            'shopping_style' => ['array'],
            'shopping_style.*' => ['string', Rule::in(ShoppingStyleCategory::values())],
            'budget' => ['nullable', 'string', Rule::in(BudgetCategory::values())],
        ]);

        DB::transaction(function () use ($data, $request) {
            DB::table('interests')->updateOrInsert(
                ['user_id' => $request->user()->id],
                [
                    'interesting_offers' => json_encode($data['interesting_offers']),
                    'shopping_style' => json_encode($data['shopping_style']),
                    'budget' => $data['budget'],
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        });

        return $this->localizedJson([
            'success' => true,
            'message' => __('api.onboarding.completed'),
        ]);
    }

    public function seller(Request $request): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        $onboarding = DB::table('shop_interests')
            ->where('user_id', $request->user()->id)
            ->first();

        return $this->localizedJson([
            'success' => true,
            'data' => [
                'target_audience' => $onboarding?->target_audience,
                'customer_reach_method' => $onboarding?->customer_reach_method,
                'price_category' => $onboarding?->price_category,
                'best_offer_time' => $onboarding?->best_offer_time,
            ],
            'is_onboarding_completed' => $onboarding !== null,
        ]);
    }

    public function storeSeller(Request $request): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        $data = $request->validate([
            'target_audience' => ['nullable', Rule::in(TargetAudienceCategory::values())],
            'customer_reach_method' => ['nullable', Rule::in(CustomerReachMethodEnum::values())],
            'price_category' => ['nullable', Rule::in(PriceCategory::values())],
            'best_offer_time' => ['nullable', Rule::in(BestOfferTimeCategory::values())],
        ]);

        DB::transaction(function () use ($data, $request) {
            DB::table('shop_interests')->updateOrInsert(
                ['user_id' => $request->user()->id],
                [
                    'target_audience' => $data['target_audience'] ?? null,
                    'customer_reach_method' => $data['customer_reach_method'] ?? null,
                    'price_category' => $data['price_category'] ?? null,
                    'best_offer_time' => $data['best_offer_time'] ?? null,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        });

        return $this->localizedJson([
            'success' => true,
            'message' => __('api.onboarding.completed'),
        ]);
    }

    private function decodeJsonArray(?string $value): array
    {
        if (!$value) {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }
}
