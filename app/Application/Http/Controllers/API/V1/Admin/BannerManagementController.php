<?php

namespace App\Application\Http\Controllers\API\V1\Admin;

use App\Application\Http\Controllers\Controller;
use App\Application\Http\Requests\Admin\ApproveBannerRequest;
use App\Application\Http\Requests\Admin\RejectBannerRequest;
use App\Application\Http\Requests\Admin\UpdateBannerRequest;
use App\Application\Http\Resources\BannerResource;
use App\Domain\Banner\Actions\ApproveBanner;
use App\Domain\Banner\Actions\DeleteBanner;
use App\Domain\Banner\Actions\RejectBanner;
use App\Domain\Banner\Actions\UpdateBanner;
use App\Domain\Banner\Models\Banner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BannerManagementController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        $query = Banner::query()
            ->with(['store', 'requestedBy', 'approvedBy']);

        // Filter by Status
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Filter by Store ID
        if ($request->filled('store_id')) {
            $query->where('store_id', $request->input('store_id'));
        }

        // Filter by is_active
        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN));
        }

        // Search by discount_label or cta_label
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('discount_label', 'LIKE', "%{$search}%")
                  ->orWhere('cta_label', 'LIKE', "%{$search}%");
            });
        }

        $query->orderBy('created_at', 'desc');

        $banners = $query->paginate($request->integer('per_page', 20));

        return $this->localizedJson([
            'message' => 'Banners retrieved successfully.',
            'data' => BannerResource::collection($banners->items()),
            'meta' => [
                'current_page' => $banners->currentPage(),
                'last_page' => $banners->lastPage(),
                'total' => $banners->total(),
            ]
        ]);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        $banner = Banner::with(['store', 'requestedBy', 'approvedBy', 'offers', 'branches'])->findOrFail($id);

        return $this->localizedJson([
            'message' => 'Banner retrieved successfully.',
            'data' => new BannerResource($banner),
        ]);
    }

    public function approve(
        ApproveBannerRequest $request,
        string $id,
        ApproveBanner $action
    ): JsonResponse {
        $this->applyAuthenticatedLocale($request);

        $banner = Banner::findOrFail($id);

        try {
            $banner = $action->execute(
                $banner,
                $request->user()
            );

            // Optional notes can be saved somewhere or logged if needed. Currently no specific field for notes.
            
            return $this->localizedJson([
                'message' => 'Banner approved successfully.',
                'data' => new BannerResource($banner),
            ]);
        } catch (\Exception $e) {
            return $this->localizedJson([
                'message' => $e->getMessage(),
                'success' => false,
            ], 400);
        }
    }

    public function reject(
        RejectBannerRequest $request,
        string $id,
        RejectBanner $action
    ): JsonResponse {
        $this->applyAuthenticatedLocale($request);

        $banner = Banner::findOrFail($id);

        try {
            $banner = $action->execute(
                $banner,
                $request->user(),
                $request->input('reason')
            );

            return $this->localizedJson([
                'message' => 'Banner rejected successfully.',
                'data' => new BannerResource($banner),
            ]);
        } catch (\Exception $e) {
            return $this->localizedJson([
                'message' => $e->getMessage(),
                'success' => false,
            ], 400);
        }
    }

    public function update(
        UpdateBannerRequest $request,
        string $id,
        UpdateBanner $action
    ): JsonResponse {
        $this->applyAuthenticatedLocale($request);

        $banner = Banner::findOrFail($id);

        try {
            $banner = $action->execute(
                $banner,
                $request->validated()
            );

            return $this->localizedJson([
                'message' => 'Banner updated successfully.',
                'data' => new BannerResource($banner),
            ]);
        } catch (\Exception $e) {
            return $this->localizedJson([
                'message' => $e->getMessage(),
                'success' => false,
            ], 400);
        }
    }

    public function destroy(
        Request $request,
        string $id,
        DeleteBanner $action
    ): JsonResponse {
        $this->applyAuthenticatedLocale($request);
        
        if (!$request->user()->hasRole('admin')) {
             abort(403, 'Unauthorized.');
        }

        $banner = Banner::findOrFail($id);

        try {
            $action->execute($banner);

            return $this->localizedJson([
                'message' => 'Banner deleted successfully.',
            ]);
        } catch (\Exception $e) {
            return $this->localizedJson([
                'message' => $e->getMessage(),
                'success' => false,
            ], 400);
        }
    }
}
