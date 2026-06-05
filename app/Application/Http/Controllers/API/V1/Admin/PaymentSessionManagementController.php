<?php

namespace App\Application\Http\Controllers\API\V1\Admin;

use App\Application\Http\Controllers\Controller;
use App\Application\Http\Requests\Admin\PaymentSessionApproveRequest;
use App\Application\Http\Requests\Admin\PaymentSessionFailRequest;
use App\Domain\Subscription\Actions\ConfirmPaymentAction;
use App\Domain\Subscription\Enums\PaymentSessionStatus;
use App\Domain\Subscription\Models\PaymentSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentSessionManagementController extends Controller
{
    public function __construct(
        private readonly ConfirmPaymentAction $confirmPaymentAction,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        $query = PaymentSession::with(['store', 'plan']);

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('store_id')) {
            $query->where('store_id', $request->input('store_id'));
        }

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('paymob_order_id', 'like', "%{$search}%")
                  ->orWhere('paymob_transaction_id', 'like', "%{$search}%")
                  ->orWhereHas('store', function ($storeQuery) use ($search) {
                      $storeQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $sessions = $query->latest('created_at')->paginate($request->integer('per_page', 15));

        return $this->localizedJson([
            'message' => __('api.admin.payment_sessions.retrieved', ['default' => 'Payment sessions retrieved successfully.']),
            'data' => $sessions->items(),
            'meta' => [
                'current_page' => $sessions->currentPage(),
                'last_page' => $sessions->lastPage(),
                'total' => $sessions->total(),
            ],
        ]);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        $session = PaymentSession::with(['store', 'plan'])->findOrFail($id);

        return $this->localizedJson([
            'message' => __('api.admin.payment_sessions.retrieved', ['default' => 'Payment session retrieved successfully.']),
            'data' => $session,
        ]);
    }

    public function approve(PaymentSessionApproveRequest $request, string $id): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        $session = PaymentSession::findOrFail($id);

        if ($session->status !== PaymentSessionStatus::PENDING) {
            return $this->localizedJson([
                'success' => false,
                'message' => __('api.admin.payment_sessions.not_pending', ['default' => 'Only pending payment sessions can be approved.']),
            ], 400);
        }

        $subscription = DB::transaction(function () use ($request, $session) {
            $session->update([
                'status' => PaymentSessionStatus::PAID,
                'paid_at' => now(),
                // Prepend admin notes to failure_reason or a dedicated notes field if exists. 
                // We'll use failure_reason to store notes since there's no notes field.
                'failure_reason' => 'Admin Approved. Method: ' . $request->validated('payment_method') . '. ' . $request->validated('notes'),
            ]);

            return $this->confirmPaymentAction->execute($session->store, $session->id);
        });

        return $this->localizedJson([
            'message' => __('api.admin.payment_sessions.approved', ['default' => 'Payment session approved and subscription activated.']),
        ]);
    }

    public function fail(PaymentSessionFailRequest $request, string $id): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        $session = PaymentSession::findOrFail($id);

        if ($session->status !== PaymentSessionStatus::PENDING) {
            return $this->localizedJson([
                'success' => false,
                'message' => __('api.admin.payment_sessions.not_pending', ['default' => 'Only pending payment sessions can be marked as failed.']),
            ], 400);
        }

        $session->update([
            'status' => PaymentSessionStatus::FAILED,
            'failed_at' => now(),
            'failure_reason' => 'Admin marked as failed: ' . $request->input('reason', ''),
        ]);

        return $this->localizedJson([
            'message' => __('api.admin.payment_sessions.failed', ['default' => 'Payment session marked as failed.']),
        ]);
    }
}
