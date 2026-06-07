<?php

namespace App\Application\Http\Controllers\API\V1\Admin;

use App\Application\Http\Controllers\Controller;
use App\Application\Http\Requests\Admin\AdminClaimFilterRequest;
use App\Application\Http\Requests\Admin\CancelClaimRequest;
use App\Domain\Banner\Enums\BannerClaimStatus;
use App\Domain\Banner\Events\BannerClaimCancelled;
use App\Domain\Banner\Models\BannerClaim;
use Illuminate\Http\JsonResponse;

class AdminBannerClaimController extends Controller
{
    public function index(AdminClaimFilterRequest $request): JsonResponse
    {
        $query = BannerClaim::with(['user', 'store', 'banner', 'redeemedBy'])
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
            'message' => 'Banner claims retrieved successfully.',
            'data' => $claims->items(),
            'meta' => [
                'current_page' => $claims->currentPage(),
                'last_page' => $claims->lastPage(),
                'per_page' => $claims->perPage(),
                'total' => $claims->total(),
            ]
        ]);
    }

    public function show(BannerClaim $bannerClaim): JsonResponse
    {
        $bannerClaim->load(['user', 'store', 'banner', 'redeemedBy']);

        return response()->json([
            'success' => true,
            'message' => 'Banner claim retrieved successfully.',
            'data' => $bannerClaim,
        ]);
    }

    public function cancel(CancelClaimRequest $request, BannerClaim $bannerClaim): JsonResponse
    {
        if ($bannerClaim->status !== BannerClaimStatus::ACTIVE) {
            return response()->json([
                'success' => false,
                'message' => 'Only active claims can be cancelled.',
            ], 422);
        }

        $bannerClaim->update([
            'status' => BannerClaimStatus::CANCELLED,
            'cancellation_reason' => $request->reason,
        ]);

        event(new BannerClaimCancelled($bannerClaim));

        return response()->json([
            'success' => true,
            'message' => 'Banner claim cancelled successfully.',
            'data' => $bannerClaim->fresh()->load(['user', 'store', 'banner', 'redeemedBy']),
        ]);
    }
}
