<?php

namespace App\Application\Http\Controllers\API\V1\Admin;

use App\Application\Http\Controllers\Controller;
use App\Application\Http\Requests\Admin\AdminClaimFilterRequest;
use App\Application\Http\Requests\Admin\CancelClaimRequest;
use App\Domain\Product\Enums\OfferClaimStatus;
use App\Domain\Product\Events\OfferClaimCancelled;
use App\Domain\Product\Models\OfferClaim;
use Illuminate\Http\JsonResponse;

class AdminOfferClaimController extends Controller
{
    public function index(AdminClaimFilterRequest $request): JsonResponse
    {
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
            'message' => 'Offer claims retrieved successfully.',
            'data' => $claims->items(),
            'meta' => [
                'current_page' => $claims->currentPage(),
                'last_page' => $claims->lastPage(),
                'per_page' => $claims->perPage(),
                'total' => $claims->total(),
            ]
        ]);
    }

    public function show(OfferClaim $offerClaim): JsonResponse
    {
        $offerClaim->load(['user', 'store', 'product', 'offer', 'redeemedBy']);

        return response()->json([
            'success' => true,
            'message' => 'Offer claim retrieved successfully.',
            'data' => $offerClaim,
        ]);
    }

    public function cancel(CancelClaimRequest $request, OfferClaim $offerClaim): JsonResponse
    {
        if ($offerClaim->status !== OfferClaimStatus::ACTIVE) {
            return response()->json([
                'success' => false,
                'message' => 'Only active claims can be cancelled.',
            ], 422);
        }

        $offerClaim->update([
            'status' => OfferClaimStatus::CANCELLED,
            'cancellation_reason' => $request->reason,
        ]);

        event(new OfferClaimCancelled($offerClaim));

        return response()->json([
            'success' => true,
            'message' => 'Offer claim cancelled successfully.',
            'data' => $offerClaim->fresh()->load(['user', 'store', 'product', 'offer', 'redeemedBy']),
        ]);
    }
}
