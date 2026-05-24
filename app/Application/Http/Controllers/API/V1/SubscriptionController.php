<?php

namespace App\Application\Http\Controllers\API\V1;

use App\Application\Http\Controllers\Controller;
use App\Application\Http\Requests\ConfirmPaymentRequest;
use App\Application\Http\Requests\InitiatePaymentRequest;
use App\Application\Http\Resources\EntitlementResource;
use App\Application\Http\Resources\SubscriptionHistoryResource;
use App\Application\Http\Resources\SubscriptionOverviewResource;
use App\Application\Http\Resources\SubscriptionPlanResource;
use App\Application\Http\Resources\SubscriptionStatusResource;
use App\Domain\Store\Models\Store;
use App\Domain\Subscription\Actions\ConfirmPaymentAction;
use App\Domain\Subscription\Actions\InitiatePaymentAction;
use App\Domain\Subscription\Enums\HistoryStatus;
use App\Domain\Subscription\Exceptions\PaymentSessionAlreadyExistsException;
use App\Domain\Subscription\Exceptions\PaymentSessionAlreadyUsedException;
use App\Domain\Subscription\Exceptions\PaymentSessionExpiredException;
use App\Domain\Subscription\Exceptions\PaymentSessionNotFoundException;
use App\Domain\Subscription\Exceptions\PaymobApiException;
use App\Domain\Subscription\Models\SubscriptionHistory;
use App\Domain\Subscription\Models\SubscriptionPlan;
use App\Domain\Subscription\Repositories\SubscriptionRepository;
use App\Domain\Subscription\Services\EntitlementService;
use App\Domain\Subscription\Services\PaymobService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class SubscriptionController extends Controller
{
    public function __construct(
        private readonly InitiatePaymentAction $initiatePaymentAction,
        private readonly ConfirmPaymentAction $confirmPaymentAction,
        private readonly SubscriptionRepository $subscriptionRepository,
        private readonly EntitlementService $entitlementService,
        private readonly PaymobService $paymobService,
    ) {}

    /**
     * Initiate a payment session for a subscription plan.
     */
    public function initiatePayment(InitiatePaymentRequest $request, Store $store): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);
        Gate::authorize('manageSubscription', $store);

        $plan = SubscriptionPlan::findOrFail($request->validated('plan_id'));
        $billingCycle = $request->validated('billing_cycle');

        try {
            $session = $this->initiatePaymentAction->execute($store, $plan, $billingCycle);

            return $this->localizedJson([
                'success' => true,
                'data' => [
                    'session_id' => $session->id,
                    'client_secret' => $session->payment_url,
                    'public_key' => $this->paymobService->getPublicKey(),
                    'expires_at' => $session->expires_at?->toIso8601String(),
                ],
            ]);
        } catch (PaymentSessionAlreadyExistsException $e) {
            return $this->localizedJson([
                'success' => false,
                'message' => $e->getMessage(),
                'error_code' => 'PAYMENT_SESSION_ALREADY_USED',
            ], 409);
        } catch (PaymobApiException $e) {
            return $this->localizedJson([
                'success' => false,
                'message' => 'Payment gateway error. Please try again later.',
            ], 502);
        }
    }

    /**
     * Confirm a payment session and activate subscription.
     */
    public function confirmPayment(ConfirmPaymentRequest $request, Store $store): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);
        Gate::authorize('manageSubscription', $store);

        $sessionId = $request->validated('session_id');

        try {
            $subscription = $this->confirmPaymentAction->execute($store, $sessionId);

            return $this->localizedJson([
                'success' => true,
                'data' => new SubscriptionOverviewResource($subscription->load('plan')),
            ]);
        } catch (PaymentSessionNotFoundException $e) {
            return $this->localizedJson([
                'success' => false,
                'message' => $e->getMessage(),
                'error_code' => 'PAYMENT_SESSION_NOT_FOUND',
            ], 404);
        } catch (PaymentSessionAlreadyUsedException $e) {
            return $this->localizedJson([
                'success' => false,
                'message' => $e->getMessage(),
                'error_code' => 'PAYMENT_SESSION_ALREADY_USED',
            ], 409);
        } catch (PaymentSessionExpiredException $e) {
            return $this->localizedJson([
                'success' => false,
                'message' => $e->getMessage(),
                'error_code' => 'PAYMENT_SESSION_EXPIRED',
            ], 410);
        }
    }

    /**
     * Get full subscription overview with usage.
     */
    public function overview(Request $request, Store $store): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);
        Gate::authorize('manageSubscription', $store);

        $subscription = $this->subscriptionRepository->findByStore($store->id);

        if ($subscription === null) {
            return $this->localizedJson([
                'success' => true,
                'data' => [
                    'status' => 'none',
                    'plan' => null,
                    'usage' => null,
                    'available_plans' => SubscriptionPlanResource::collection(
                        SubscriptionPlan::where('is_active', true)->orderBy('sort_order')->get()
                    ),
                ],
            ]);
        }

        $subscription->load('plan');
        $subscription->usage = $this->entitlementService->getCurrentUsage($store);

        return $this->localizedJson([
            'success' => true,
            'data' => new SubscriptionOverviewResource($subscription),
        ]);
    }

    /**
     * Get lightweight subscription status for banners.
     */
    public function status(Request $request, Store $store): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);
        Gate::authorize('manageSubscription', $store);

        $subscription = $this->subscriptionRepository->findByStore($store->id);

        if ($subscription === null) {
            return $this->localizedJson([
                'success' => true,
                'data' => [
                    'status' => 'none',
                    'days_remaining' => null,
                    'message' => 'No active subscription. Subscribe to a plan to unlock features.',
                ],
            ]);
        }

        return $this->localizedJson([
            'success' => true,
            'data' => new SubscriptionStatusResource($subscription),
        ]);
    }

    /**
     * Get available subscription plans.
     */
    public function plans(Request $request, Store $store): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);
        Gate::authorize('manageSubscription', $store);

        $plans = SubscriptionPlan::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return $this->localizedJson([
            'success' => true,
            'data' => SubscriptionPlanResource::collection($plans),
        ]);
    }

    /**
     * Get paginated subscription history.
     */
    public function history(Request $request, Store $store): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);
        Gate::authorize('manageSubscription', $store);

        $validated = $request->validate([
            'status' => ['nullable', 'in:' . implode(',', array_column(HistoryStatus::cases(), 'value'))],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $history = SubscriptionHistory::where('store_id', $store->id)
            ->with('plan')
            ->when(
                filled($validated['status'] ?? null),
                fn ($query) => $query->where('status', $validated['status'])
            )
            ->orderByDesc('created_at')
            ->paginate($validated['per_page'] ?? 15);

        return $this->localizedJson([
            'success' => true,
            'data' => SubscriptionHistoryResource::collection($history->getCollection())->resolve($request),
            'meta' => [
                'current_page' => $history->currentPage(),
                'last_page' => $history->lastPage(),
                'per_page' => $history->perPage(),
                'total' => $history->total(),
            ],
        ]);
    }

    /**
     * Get current entitlements and usage.
     */
    public function entitlements(Request $request, Store $store): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);
        Gate::authorize('manageSubscription', $store);

        $entitlementData = $this->entitlementService->getEntitlements($store);

        return $this->localizedJson([
            'success' => true,
            'data' => new EntitlementResource($entitlementData),
        ]);
    }
}
