<?php

namespace App\Http\Middleware;

use App\Domain\Store\Models\Store;
use App\Domain\Subscription\Enums\SubscriptionStatus;
use App\Domain\Subscription\Repositories\SubscriptionRepository;
use App\Domain\Subscription\Services\EntitlementService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscription
{
    public function __construct(
        private readonly SubscriptionRepository $subscriptionRepository,
        private readonly EntitlementService $entitlementService,
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ?string $resourceType = null, ?string $feature = null): Response
    {
        $store = $this->resolveStore($request);

        if (! $store) {
            return response()->json([
                'success' => false,
                'message' => __('api.store.not_found'),
                'error_code' => 'STORE_NOT_FOUND',
            ], 404);
        }

        $subscription = $this->subscriptionRepository->findByStore($store->id);
        $status = $subscription?->status ?? SubscriptionStatus::NONE;

        // Check blocked statuses
        if ($status === SubscriptionStatus::NONE) {
            return response()->json([
                'success' => false,
                'message' => __('api.subscription.required'),
                'error_code' => 'SUBSCRIPTION_REQUIRED',
            ], 403);
        }

        if ($status === SubscriptionStatus::SUSPENDED) {
            return response()->json([
                'success' => false,
                'message' => __('api.subscription.store_suspended'),
                'error_code' => 'STORE_SUSPENDED',
            ], 403);
        }

        if ($status === SubscriptionStatus::ARCHIVED) {
            return response()->json([
                'success' => false,
                'message' => __('api.subscription.store_archived'),
                'error_code' => 'STORE_ARCHIVED',
            ], 403);
        }

        // Handle degraded status: allow reads, block writes that exceed free-tier limits
        if ($status === SubscriptionStatus::DEGRADED) {
            if ($this->isWriteRequest($request)) {
                // Block write operations that exceed free-tier limits
                if ($resourceType && ! $this->entitlementService->checkLimit($store, $resourceType)) {
                    return response()->json([
                        'success' => false,
                        'message' => __('api.subscription.limit_reached'),
                        'error_code' => 'SUBSCRIPTION_LIMIT_REACHED',
                    ], 403);
                }

                if ($feature && ! $this->entitlementService->checkFeatureAccess($store, $feature)) {
                    return response()->json([
                        'success' => false,
                        'message' => __('api.subscription.feature_locked'),
                        'error_code' => 'SUBSCRIPTION_FEATURE_LOCKED',
                    ], 403);
                }
            }

            return $next($request);
        }

        // For active, trial, and grace statuses: check resource limits and feature access
        if ($resourceType && ! $this->entitlementService->checkLimit($store, $resourceType)) {
            return response()->json([
                'success' => false,
                'message' => __('api.subscription.limit_reached'),
                'error_code' => 'SUBSCRIPTION_LIMIT_REACHED',
            ], 403);
        }

        if ($feature && ! $this->entitlementService->checkFeatureAccess($store, $feature)) {
            return response()->json([
                'success' => false,
                'message' => __('api.subscription.feature_locked'),
                'error_code' => 'SUBSCRIPTION_FEATURE_LOCKED',
            ], 403);
        }

        return $next($request);
    }

    /**
     * Resolve the store from the route parameter.
     */
    private function resolveStore(Request $request): ?Store
    {
        $store = $request->route('store');

        if ($store instanceof Store) {
            return $store;
        }

        if ($store) {
            return Store::find($store);
        }

        return null;
    }

    /**
     * Determine if the request is a write operation (POST, PUT, PATCH, DELETE).
     */
    private function isWriteRequest(Request $request): bool
    {
        return in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE']);
    }
}
