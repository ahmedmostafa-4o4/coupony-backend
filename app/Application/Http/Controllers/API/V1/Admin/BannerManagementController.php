<?php

namespace App\Application\Http\Controllers\API\V1\Admin;

use App\Application\Http\Controllers\Controller;
use App\Application\Http\Requests\AdminUpdateBannerRequest;
use App\Application\Http\Requests\ApproveBannerRequest;
use App\Application\Http\Requests\RejectBannerRequest;
use App\Application\Http\Resources\BannerResource;
use App\Domain\Banner\Enums\BannerStatus;
use App\Domain\Banner\Models\Banner;
use App\Domain\Banner\Services\BannerService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BannerManagementController extends Controller
{
    public function __construct(private readonly BannerService $banners) {}

    public function index(Request $request): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        $validated = $request->validate([
            'status' => ['nullable', 'in:'.implode(',', BannerStatus::values())],
            'store_id' => ['nullable', 'uuid', 'exists:stores,id'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $results = Banner::query()
            ->with(['store', 'requestedBy', 'approvedBy', 'branches', 'offers.product.images', 'offers.product.variants.attributes'])
            ->withCount('likes')
            ->when(
                filled($validated['status'] ?? null),
                fn ($query) => $query->where('status', $validated['status'])
            )
            ->when(
                filled($validated['store_id'] ?? null),
                fn ($query) => $query->where('store_id', $validated['store_id'])
            )
            ->latest()
            ->paginate($validated['per_page'] ?? 15);

        return $this->paginatedResponse(
            BannerResource::collection($results->getCollection())->resolve($request),
            'Banners retrieved successfully.',
            $results
        );
    }

    public function show(Request $request, Banner $banner): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        return $this->successResponse(
            new BannerResource($this->banners->loadForManagement($banner)),
            'Banner details retrieved successfully.'
        );
    }

    public function update(AdminUpdateBannerRequest $request, Banner $banner): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        try {
            $banner = $this->banners->updateAdminBanner($banner, $request->validated());

            return $this->successResponse(new BannerResource($banner), 'Banner updated successfully.');
        } catch (\Throwable $throwable) {
            report($throwable);

            return $this->errorResponse('Unable to update the banner.', 500);
        }
    }

    public function approve(ApproveBannerRequest $request, Banner $banner): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        try {
            $banner = $this->banners->approve($banner, $request->user(), $request->validated());

            return $this->successResponse(new BannerResource($banner), 'Banner approved successfully.');
        } catch (\Throwable $throwable) {
            report($throwable);

            return $this->errorResponse('Unable to approve the banner.', 500);
        }
    }

    public function reject(RejectBannerRequest $request, Banner $banner): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        try {
            $banner = $this->banners->reject($banner, $request->validated('reason'));

            return $this->successResponse(new BannerResource($banner), 'Banner rejected successfully.');
        } catch (\Throwable $throwable) {
            report($throwable);

            return $this->errorResponse('Unable to reject the banner.', 500);
        }
    }

    private function successResponse(mixed $data, string $message, int $status = 200): JsonResponse
    {
        return $this->localizedJson([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    private function errorResponse(string $message, int $status = 400): JsonResponse
    {
        return $this->localizedJson([
            'success' => false,
            'message' => $message,
        ], $status);
    }

    private function paginatedResponse(mixed $data, string $message, LengthAwarePaginator $paginator): JsonResponse
    {
        return $this->localizedJson([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }
}
