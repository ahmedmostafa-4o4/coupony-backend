<?php

namespace App\Application\Http\Controllers\API\V1\Admin;

use App\Application\Http\Controllers\Controller;
use App\Application\Http\Requests\Admin\AdminClaimFilterRequest;
use App\Application\Http\Requests\Admin\CancelClaimRequest;
use App\Domain\Product\Enums\OfferClaimStatus;
use App\Domain\Product\Events\OfferClaimCancelled;
use App\Domain\Product\Models\OfferClaim;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminOfferClaimController extends Controller
{
    public function index(AdminClaimFilterRequest $request): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        $query = OfferClaim::with(['user', 'store', 'product', 'offer', 'redeemedBy'])
            ->latest();

        if ($request->filled('store_id')) {
            $query->where('store_id', $request->store_id);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $claims = $query->paginate($request->integer('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => __('api.offer_claim.retrieved'),
            'data' => $claims->items(),
            'meta' => [
                'current_page' => $claims->currentPage(),
                'last_page' => $claims->lastPage(),
                'per_page' => $claims->perPage(),
                'total' => $claims->total(),
            ]
        ]);
    }

    public function show(Request $request, OfferClaim $offerClaim): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        $offerClaim->load(['user', 'store', 'product', 'offer', 'redeemedBy']);

        return response()->json([
            'success' => true,
            'message' => __('api.offer_claim.details_retrieved'),
            'data' => $offerClaim,
        ]);
    }

    public function cancel(CancelClaimRequest $request, OfferClaim $offerClaim): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        if ($offerClaim->status !== OfferClaimStatus::ACTIVE) {
            return response()->json([
                'success' => false,
                'message' => __('api.offer_claim.only_active_cancel'),
            ], 422);
        }

        $offerClaim->update([
            'status' => OfferClaimStatus::CANCELLED,
            'cancellation_reason' => $request->reason,
        ]);

        event(new OfferClaimCancelled($offerClaim));

        return response()->json([
            'success' => true,
            'message' => __('api.offer_claim.cancelled'),
            'data' => $offerClaim->fresh()->load(['user', 'store', 'product', 'offer', 'redeemedBy']),
        ]);
    }
}
