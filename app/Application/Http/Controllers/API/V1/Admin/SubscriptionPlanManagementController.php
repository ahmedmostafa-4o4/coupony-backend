<?php

namespace App\Application\Http\Controllers\API\V1\Admin;

use App\Application\Http\Controllers\Controller;
use App\Application\Http\Requests\Admin\SubscriptionPlanStoreRequest;
use App\Application\Http\Requests\Admin\SubscriptionPlanUpdateRequest;
use App\Application\Http\Resources\SubscriptionPlanResource;
use App\Domain\Subscription\Models\SubscriptionPlan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SubscriptionPlanManagementController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        $query = SubscriptionPlan::query();

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        } else {
            $query->where('is_active', true);
        }

        $plans = $query->orderBy('sort_order')->get();

        return $this->localizedJson([
            'message' => __('api.admin.subscription_plans.retrieved', ['default' => 'Subscription plans retrieved successfully.']),
            'data' => SubscriptionPlanResource::collection($plans),
        ]);
    }

    public function store(SubscriptionPlanStoreRequest $request): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        $data = $request->validated();
        $data['features'] = $data['features'] ?? [];

        $plan = SubscriptionPlan::create($data);

        return $this->localizedJson([
            'message' => __('api.admin.subscription_plans.created', ['default' => 'Subscription plan created successfully.']),
            'data' => new SubscriptionPlanResource($plan),
        ], 201);
    }

    public function show(Request $request, SubscriptionPlan $subscriptionPlan): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        return $this->localizedJson([
            'message' => __('api.admin.subscription_plans.retrieved', ['default' => 'Subscription plan retrieved successfully.']),
            'data' => new SubscriptionPlanResource($subscriptionPlan),
        ]);
    }

    public function update(SubscriptionPlanUpdateRequest $request, SubscriptionPlan $subscriptionPlan): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        // Implements Plan Versioning to grandfather existing subscriptions
        $newPlan = DB::transaction(function () use ($request, $subscriptionPlan) {
            // 1. Rename the old plan's slug so the new plan can use it
            $oldSlug = $subscriptionPlan->slug;
            $subscriptionPlan->update([
                'is_active' => false,
                'slug' => $oldSlug . '-legacy-' . time(),
            ]);

            // 2. Create the new plan with the updated details
            $data = array_merge(
                $subscriptionPlan->only([
                    'name', 'slug', 'description', 'price_monthly', 'price_yearly',
                    'currency', 'max_products', 'max_employees', 'max_branches',
                    'features', 'grace_period_days', 'degraded_period_days', 'sort_order'
                ]),
                $request->validated()
            );

            // Restore the original slug if it wasn't explicitly changed in the request
            if (!$request->has('slug')) {
                $data['slug'] = $oldSlug;
            }

            return SubscriptionPlan::create($data);
        });

        return $this->localizedJson([
            'message' => __('api.admin.subscription_plans.updated', ['default' => 'Subscription plan updated successfully. Existing subscriptions have been grandfathered.']),
            'data' => new SubscriptionPlanResource($newPlan),
        ]);
    }

    public function destroy(Request $request, SubscriptionPlan $subscriptionPlan): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        // Soft delete by setting is_active = false
        $subscriptionPlan->update([
            'is_active' => false,
            'slug' => $subscriptionPlan->slug . '-deleted-' . time(),
        ]);

        return $this->localizedJson([
            'message' => __('api.admin.subscription_plans.deleted', ['default' => 'Subscription plan deleted successfully.']),
        ]);
    }
}
