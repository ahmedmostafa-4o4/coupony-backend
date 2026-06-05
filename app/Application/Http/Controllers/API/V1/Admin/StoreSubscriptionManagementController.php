<?php

namespace App\Application\Http\Controllers\API\V1\Admin;

use App\Application\Http\Controllers\Controller;
use App\Application\Http\Requests\Admin\StoreSubscriptionAssignRequest;
use App\Application\Http\Requests\Admin\StoreSubscriptionCancelRequest;
use App\Application\Http\Requests\Admin\StoreSubscriptionSuspendRequest;
use App\Application\Http\Resources\SubscriptionOverviewResource;
use App\Domain\Store\Models\Store;
use App\Domain\Subscription\Actions\ConfirmPaymentAction;
use App\Domain\Subscription\Actions\TransitionSubscriptionAction;
use App\Domain\Subscription\Enums\PaymentSessionStatus;
use App\Domain\Subscription\Enums\SubscriptionStatus;
use App\Domain\Subscription\Models\PaymentSession;
use App\Domain\Subscription\Models\Subscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StoreSubscriptionManagementController extends Controller
{
    public function __construct(
        private readonly ConfirmPaymentAction $confirmPaymentAction,
        private readonly TransitionSubscriptionAction $transitionSubscriptionAction,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        $query = Subscription::with(['store', 'plan']);

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('store_id')) {
            $query->where('store_id', $request->input('store_id'));
        }

        if ($request->has('plan_id')) {
            $query->where('plan_id', $request->input('plan_id'));
        }

        $subscriptions = $query->latest('updated_at')->paginate($request->integer('per_page', 15));

        return $this->localizedJson([
            'message' => __('api.admin.subscriptions.retrieved', ['default' => 'Subscriptions retrieved successfully.']),
            'data' => SubscriptionOverviewResource::collection($subscriptions),
            'meta' => [
                'current_page' => $subscriptions->currentPage(),
                'last_page' => $subscriptions->lastPage(),
                'total' => $subscriptions->total(),
            ],
        ]);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        $subscription = Subscription::with(['store', 'plan', 'auditLogs'])->findOrFail($id);

        return $this->localizedJson([
            'message' => __('api.admin.subscriptions.retrieved', ['default' => 'Subscription retrieved successfully.']),
            'data' => new SubscriptionOverviewResource($subscription),
        ]);
    }

    public function assign(StoreSubscriptionAssignRequest $request, Store $store): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        $subscription = DB::transaction(function () use ($request, $store) {
            // Create a $0 "Admin Override" payment session that is instantly PAID
            $session = PaymentSession::create([
                'store_id' => $store->id,
                'plan_id' => $request->validated('plan_id'),
                'billing_cycle' => $request->validated('billing_cycle'),
                'amount' => 0.00,
                'currency' => 'EGP',
                'status' => PaymentSessionStatus::PAID,
                'paymob_order_id' => 'ADMIN_OVERRIDE_' . time(),
                'paymob_transaction_id' => 'ADMIN_OVERRIDE_' . time(),
                'payment_url' => 'admin-override',
                'expires_at' => now()->addMinutes(5),
                'paid_at' => now(),
            ]);

            // Execute the ConfirmPaymentAction which handles the complex logic of subscription updates, history, and events
            return $this->confirmPaymentAction->execute($store, $session->id);
        });

        return $this->localizedJson([
            'message' => __('api.admin.subscriptions.assigned', ['default' => 'Subscription manually assigned successfully.']),
            'data' => new SubscriptionOverviewResource($subscription),
        ]);
    }

    public function cancel(StoreSubscriptionCancelRequest $request, string $id): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        $subscription = Subscription::findOrFail($id);
        
        $reason = $request->input('reason', 'Cancelled by Admin');

        $subscription = $this->transitionSubscriptionAction->execute(
            $subscription,
            SubscriptionStatus::ARCHIVED,
            $reason
        );

        return $this->localizedJson([
            'message' => __('api.admin.subscriptions.cancelled', ['default' => 'Subscription cancelled successfully.']),
            'data' => new SubscriptionOverviewResource($subscription),
        ]);
    }

    public function suspend(StoreSubscriptionSuspendRequest $request, string $id): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        $subscription = Subscription::findOrFail($id);
        
        $reason = $request->input('reason', 'Suspended by Admin');

        $subscription = $this->transitionSubscriptionAction->execute(
            $subscription,
            SubscriptionStatus::SUSPENDED,
            $reason
        );

        return $this->localizedJson([
            'message' => __('api.admin.subscriptions.suspended', ['default' => 'Subscription suspended successfully.']),
            'data' => new SubscriptionOverviewResource($subscription),
        ]);
    }
}
