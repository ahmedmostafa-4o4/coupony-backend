<?php

namespace App\Domain\Subscription\Services;

use App\Domain\Store\Models\Store;
use App\Domain\Subscription\DTOs\EntitlementData;
use App\Domain\Subscription\Models\Subscription;
use App\Domain\Subscription\Repositories\SubscriptionRepository;

class EntitlementService
{
    public function __construct(
        private readonly SubscriptionRepository $subscriptionRepository,
    ) {}

    /**
     * Get full entitlements for a store including plan limits, current usage, and remaining capacity.
     */
    public function getEntitlements(Store $store): EntitlementData
    {
        $subscription = $this->subscriptionRepository->findByStore($store->id);

        if (! $subscription || ! $subscription->plan) {
            return new EntitlementData(
                limits: [
                    'products' => ['limit' => 0, 'usage' => 0, 'remaining' => 0],
                    'employees' => ['limit' => 0, 'usage' => 0, 'remaining' => 0],
                    'branches' => ['limit' => 0, 'usage' => 0, 'remaining' => 0],
                ],
                features: [],
            );
        }

        $plan = $subscription->plan;
        $usage = $this->getCurrentUsage($store);

        $limits = [
            'products' => [
                'limit' => $plan->max_products,
                'usage' => $usage['products'],
                'remaining' => max(0, $plan->max_products - $usage['products']),
            ],
            'employees' => [
                'limit' => $plan->max_employees,
                'usage' => $usage['employees'],
                'remaining' => max(0, $plan->max_employees - $usage['employees']),
            ],
            'branches' => [
                'limit' => $plan->max_branches,
                'usage' => $usage['branches'],
                'remaining' => max(0, $plan->max_branches - $usage['branches']),
            ],
        ];

        $features = is_array($plan->features) ? $plan->features : [];

        return new EntitlementData(
            limits: $limits,
            features: $features,
        );
    }

    /**
     * Check if the store is within the plan limit for a given resource type.
     * Returns true if current usage is below the plan limit.
     * Returns false if at or over limit, or if no subscription/plan exists.
     */
    public function checkLimit(Store $store, string $resourceType): bool
    {
        $subscription = $this->subscriptionRepository->findByStore($store->id);

        if (! $subscription || ! $subscription->plan) {
            return false;
        }

        $plan = $subscription->plan;
        $usage = $this->getCurrentUsage($store);

        $limit = match ($resourceType) {
            'products' => $plan->max_products,
            'employees' => $plan->max_employees,
            'branches' => $plan->max_branches,
            default => 0,
        };

        $currentUsage = $usage[$resourceType] ?? 0;

        return $currentUsage < $limit;
    }

    /**
     * Check if a store has access to a specific feature based on their plan.
     * Returns true if the feature flag exists and is true in the plan's features JSON.
     * Returns false otherwise.
     */
    public function checkFeatureAccess(Store $store, string $feature): bool
    {
        $subscription = $this->subscriptionRepository->findByStore($store->id);

        if (! $subscription || ! $subscription->plan) {
            return false;
        }

        $features = $subscription->plan->features;

        if (! is_array($features)) {
            return false;
        }

        return ! empty($features[$feature]);
    }

    /**
     * Query actual resource counts for a store.
     *
     * @return array{products: int, employees: int, branches: int}
     */
    public function getCurrentUsage(Store $store): array
    {
        return [
            'products' => $store->products()->count(),
            'employees' => $store->employees()->count(),
            'branches' => $store->addresses()->count(),
        ];
    }
}
