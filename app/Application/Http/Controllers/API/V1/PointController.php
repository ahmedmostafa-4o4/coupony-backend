<?php

namespace App\Application\Http\Controllers\API\V1;

use App\Application\Http\Controllers\Controller;
use App\Application\Http\Resources\StorePointsResource;
use App\Application\Http\Resources\StorePointTransactionResource;
use App\Application\Http\Resources\UserPointsResource;
use App\Application\Http\Resources\UserPointTransactionResource;
use App\Domain\Points\Services\PointsService;
use App\Domain\Store\Models\Store;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PointController extends Controller
{
    public function __construct(private readonly PointsService $points)
    {
    }

    public function showMyPoints(Request $request): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        return $this->localizedJson([
            'message' => 'Points retrieved successfully.',
            'data' => new UserPointsResource($this->points->getOrCreateUserPoints($request->user())),
        ]);
    }

    public function myTransactions(Request $request): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        $validated = $request->validate([
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $transactions = $request->user()
            ->pointTransactions()
            ->latest()
            ->paginate($validated['per_page'] ?? 15);

        return $this->localizedJson([
            'message' => 'Point transactions retrieved successfully.',
            'data' => UserPointTransactionResource::collection($transactions->getCollection())->resolve($request),
            'meta' => $this->paginationMeta($transactions),
        ]);
    }

    public function showStorePoints(Request $request, Store $store): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);
        Gate::authorize('viewPoints', $store);

        return $this->localizedJson([
            'message' => 'Store points retrieved successfully.',
            'data' => new StorePointsResource($this->points->getOrCreateStorePoints($store)),
        ]);
    }

    public function storeTransactions(Request $request, Store $store): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);
        Gate::authorize('viewPoints', $store);

        $validated = $request->validate([
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $transactions = $store->pointTransactions()
            ->latest()
            ->paginate($validated['per_page'] ?? 15);

        return $this->localizedJson([
            'message' => 'Store point transactions retrieved successfully.',
            'data' => StorePointTransactionResource::collection($transactions->getCollection())->resolve($request),
            'meta' => $this->paginationMeta($transactions),
        ]);
    }

    private function paginationMeta($paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ];
    }
}
