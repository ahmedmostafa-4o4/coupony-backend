<?php

namespace App\Application\Http\Controllers\API\V1\Admin;

use App\Application\Http\Controllers\Controller;
use App\Application\Http\Requests\Admin\AdjustPointsRequest;
use App\Application\Http\Requests\Admin\SetPointsRequest;
use App\Application\Http\Resources\StorePointsResource;
use App\Application\Http\Resources\StorePointTransactionResource;
use App\Application\Http\Resources\UserPointsResource;
use App\Application\Http\Resources\UserPointTransactionResource;
use App\Domain\Points\Services\PointsService;
use App\Domain\Store\Models\Store;
use App\Domain\User\Models\User;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class AdminPointController extends Controller
{
    public function __construct(private readonly PointsService $points) {}

    public function showUserPoints(Request $request, User $user): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        try {
            return $this->localizedJson([
                'message' => 'User points retrieved successfully.',
                'data' => new UserPointsResource($this->points->getOrCreateUserPoints($user)),
            ]);
        } catch (Throwable) {
            return $this->localizedJson([
                'message' => 'Unable to retrieve user points.',
            ], 500);
        }
    }

    public function userTransactions(Request $request, User $user): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        $validated = $request->validate([
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        try {
            $transactions = $user->pointTransactions()
                ->latest()
                ->paginate($validated['per_page'] ?? 15);

            return $this->localizedJson([
                'message' => 'User point transactions retrieved successfully.',
                'data' => UserPointTransactionResource::collection($transactions->getCollection())->resolve($request),
                'meta' => $this->paginationMeta($transactions),
            ]);
        } catch (Throwable) {
            return $this->localizedJson([
                'message' => 'Unable to retrieve user point transactions.',
            ], 500);
        }
    }

    public function addUserPoints(AdjustPointsRequest $request, User $user): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);
        $validated = $request->validated();

        try {
            $points = $this->points->addUserPoints(
                $user,
                $validated['points'],
                $validated['reason'],
                $request->user(),
                null,
                null,
                $validated['note'] ?? null,
                $validated['meta'] ?? [],
                'adjustment'
            );

            return $this->localizedJson([
                'message' => 'User points added successfully.',
                'data' => new UserPointsResource($points),
            ]);
        } catch (DomainException $exception) {
            return $this->localizedJson([
                'message' => $exception->getMessage(),
            ], 422);
        } catch (Throwable) {
            return $this->localizedJson([
                'message' => 'Unable to add user points.',
            ], 500);
        }
    }

    public function deductUserPoints(AdjustPointsRequest $request, User $user): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);
        $validated = $request->validated();

        try {
            $points = $this->points->deductUserPoints(
                $user,
                $validated['points'],
                $validated['reason'],
                $request->user(),
                null,
                null,
                $validated['note'] ?? null,
                $validated['meta'] ?? [],
                'adjustment'
            );

            return $this->localizedJson([
                'message' => 'User points deducted successfully.',
                'data' => new UserPointsResource($points),
            ]);
        } catch (DomainException $exception) {
            return $this->localizedJson([
                'message' => $exception->getMessage(),
            ], 422);
        } catch (Throwable) {
            return $this->localizedJson([
                'message' => 'Unable to deduct user points.',
            ], 500);
        }
    }

    public function setUserPoints(SetPointsRequest $request, User $user): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);
        $validated = $request->validated();

        try {
            $points = $this->points->setUserPoints(
                $user,
                $validated['points'],
                $validated['reason'],
                $request->user(),
                $validated['note'] ?? null,
                $validated['meta'] ?? []
            );

            return $this->localizedJson([
                'message' => 'User points set successfully.',
                'data' => new UserPointsResource($points),
            ]);
        } catch (DomainException $exception) {
            return $this->localizedJson([
                'message' => $exception->getMessage(),
            ], 422);
        } catch (Throwable) {
            return $this->localizedJson([
                'message' => 'Unable to set user points.',
            ], 500);
        }
    }

    public function showStorePoints(Request $request, Store $store): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        try {
            return $this->localizedJson([
                'message' => 'Store points retrieved successfully.',
                'data' => new StorePointsResource($this->points->getOrCreateStorePoints($store)),
            ]);
        } catch (Throwable) {
            return $this->localizedJson([
                'message' => 'Unable to retrieve store points.',
            ], 500);
        }
    }

    public function storeTransactions(Request $request, Store $store): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        $validated = $request->validate([
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        try {
            $transactions = $store->pointTransactions()
                ->latest()
                ->paginate($validated['per_page'] ?? 15);

            return $this->localizedJson([
                'message' => 'Store point transactions retrieved successfully.',
                'data' => StorePointTransactionResource::collection($transactions->getCollection())->resolve($request),
                'meta' => $this->paginationMeta($transactions),
            ]);
        } catch (Throwable) {
            return $this->localizedJson([
                'message' => 'Unable to retrieve store point transactions.',
            ], 500);
        }
    }

    public function addStorePoints(AdjustPointsRequest $request, Store $store): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);
        $validated = $request->validated();

        try {
            $points = $this->points->addStorePoints(
                $store,
                $validated['points'],
                $validated['reason'],
                $request->user(),
                null,
                null,
                $validated['note'] ?? null,
                $validated['meta'] ?? [],
                'adjustment'
            );

            return $this->localizedJson([
                'message' => 'Store points added successfully.',
                'data' => new StorePointsResource($points),
            ]);
        } catch (DomainException $exception) {
            return $this->localizedJson([
                'message' => $exception->getMessage(),
            ], 422);
        } catch (Throwable) {
            return $this->localizedJson([
                'message' => 'Unable to add store points.',
            ], 500);
        }
    }

    public function deductStorePoints(AdjustPointsRequest $request, Store $store): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);
        $validated = $request->validated();

        try {
            $points = $this->points->deductStorePoints(
                $store,
                $validated['points'],
                $validated['reason'],
                $request->user(),
                null,
                null,
                $validated['note'] ?? null,
                $validated['meta'] ?? [],
                'adjustment'
            );

            return $this->localizedJson([
                'message' => 'Store points deducted successfully.',
                'data' => new StorePointsResource($points),
            ]);
        } catch (DomainException $exception) {
            return $this->localizedJson([
                'message' => $exception->getMessage(),
            ], 422);
        } catch (Throwable) {
            return $this->localizedJson([
                'message' => 'Unable to deduct store points.',
            ], 500);
        }
    }

    public function setStorePoints(SetPointsRequest $request, Store $store): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);
        $validated = $request->validated();

        try {
            $points = $this->points->setStorePoints(
                $store,
                $validated['points'],
                $validated['reason'],
                $request->user(),
                $validated['note'] ?? null,
                $validated['meta'] ?? []
            );

            return $this->localizedJson([
                'message' => 'Store points set successfully.',
                'data' => new StorePointsResource($points),
            ]);
        } catch (DomainException $exception) {
            return $this->localizedJson([
                'message' => $exception->getMessage(),
            ], 422);
        } catch (Throwable) {
            return $this->localizedJson([
                'message' => 'Unable to set store points.',
            ], 500);
        }
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
