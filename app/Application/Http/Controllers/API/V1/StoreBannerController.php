<?php

namespace App\Application\Http\Controllers\API\V1;

use App\Application\Http\Controllers\Controller;
use App\Application\Http\Requests\CreateBannerRequest;
use App\Application\Http\Requests\UpdateBannerRequest;
use App\Application\Http\Resources\BannerOfferResource;
use App\Application\Http\Resources\BannerResource;
use App\Domain\Banner\Enums\BannerStatus;
use App\Domain\Banner\Models\Banner;
use App\Domain\Banner\Services\BannerService;
use App\Domain\Store\Models\Store;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class StoreBannerController extends Controller
{
    public function __construct(private readonly BannerService $banners) {}

    public function index(Request $request, Store $store): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);
        Gate::authorize('manageBanners', $store);

        $validated = $request->validate([
            'status' => ['nullable', 'in:'.implode(',', BannerStatus::values())],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = Banner::query()
            ->where('store_id', $store->id)
            ->with(['store', 'branches', 'offers.product.images', 'offers.product.variants.attributes'])
            ->withCount('likes')
            ->when(
                filled($validated['status'] ?? null),
                fn ($query) => $query->where('status', $validated['status'])
            )
            ->latest();

        $results = $query->paginate($validated['per_page'] ?? 15);

        return $this->paginatedResponse(
            BannerResource::collection($results->getCollection())->resolve($request),
            'Banners retrieved successfully.',
            $results
        );
    }

    public function store(CreateBannerRequest $request, Store $store): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);
        Gate::authorize('manageBanners', $store);

        try {
            $banner = $this->banners->create($store, $request->user(), $request->validated());

            return $this->successResponse(new BannerResource($banner), 'Banner request submitted successfully.', 201);
        } catch (\Throwable $throwable) {
            report($throwable);

            return $this->errorResponse('Unable to submit the banner request.', 500);
        }
    }

    public function show(Request $request, Store $store, Banner $banner): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);
        Gate::authorize('manageBanners', $store);
        $this->ensureStoreBanner($store, $banner);

        return $this->successResponse(
            new BannerResource($this->banners->loadForManagement($banner)),
            'Banner details retrieved successfully.'
        );
    }

    public function update(UpdateBannerRequest $request, Store $store, Banner $banner): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);
        Gate::authorize('manageBanners', $store);
        $this->ensureStoreBanner($store, $banner);

        if (! in_array($banner->status, [BannerStatus::PENDING, BannerStatus::REJECTED], true)) {
            return $this->errorResponse('Only pending or rejected banner requests can be edited by the seller.', 422);
        }

        try {
            $banner = $this->banners->updateSellerBanner($banner, $request->validated());

            return $this->successResponse(new BannerResource($banner), 'Banner request updated successfully.');
        } catch (\Throwable $throwable) {
            report($throwable);

            return $this->errorResponse('Unable to update the banner request.', 500);
        }
    }

    public function selectableOffers(Request $request, Store $store): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);
        Gate::authorize('manageBanners', $store);

        return $this->successResponse(
            BannerOfferResource::collection($this->banners->selectableOffers($store))->resolve($request),
            'Selectable banner offers retrieved successfully.'
        );
    }

    private function ensureStoreBanner(Store $store, Banner $banner): void
    {
        if ($banner->store_id !== $store->id) {
            abort(404);
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
