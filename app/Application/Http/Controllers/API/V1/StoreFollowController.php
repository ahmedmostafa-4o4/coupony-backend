<?php

namespace App\Application\Http\Controllers\API\V1;

use App\Application\Http\Controllers\Controller;
use App\Application\Http\Resources\FollowedStoreResource;
use App\Application\Http\Resources\StoreFollowerResource;
use App\Domain\Store\Actions\FollowStore;
use App\Domain\Store\Actions\ToggleStoreFollowNotification;
use App\Domain\Store\Actions\UnfollowStore;
use App\Domain\Store\Enums\StoreStatus;
use App\Domain\Store\Models\Store;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class StoreFollowController extends Controller
{
    public function __construct(
        private readonly FollowStore $followStore,
        private readonly UnfollowStore $unfollowStore,
        private readonly ToggleStoreFollowNotification $toggleNotification,
    ) {}

    /**
     * Follow a store.
     */
    public function store(Request $request, Store $store): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        if ($store->status !== StoreStatus::ACTIVE) {
            return $this->errorResponse(__('api.store_follow.store_not_found'), 404);
        }

        try {
            $follow = $this->followStore->execute($store, $request->user());

            return $this->successResponse(
                [
                    'store_id' => $store->id,
                    'is_following' => true,
                    'notification_enabled' => (bool) $follow->notification_enabled,
                    'followers_count' => (int) $store->fresh()->followers_count,
                    'followed_at' => $follow->followed_at?->toIso8601String(),
                ],
                $follow->wasRecentlyCreated
                ? __('api.store_follow.followed')
                : __('api.store_follow.already_following'),
                $follow->wasRecentlyCreated ? 201 : 200
            );
        } catch (\Throwable $e) {
            Log::error('Store follow failed', [
                'store_id' => $store->id,
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse(__('api.store_follow.follow_failed'), 500);
        }
    }

    /**
     * Unfollow a store.
     */
    public function destroy(Request $request, Store $store): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        if ($store->status !== StoreStatus::ACTIVE) {
            return $this->errorResponse(__('api.store_follow.store_not_found'), 404);
        }

        try {
            $deleted = $this->unfollowStore->execute($store, $request->user());

            if (! $deleted) {
                return $this->errorResponse(__('api.store_follow.not_following'), 422);
            }

            return $this->successResponse([
                'store_id' => $store->id,
                'is_following' => false,
                'followers_count' => (int) $store->fresh()->followers_count,
            ], __('api.store_follow.unfollowed'));
        } catch (\Throwable $e) {
            Log::error('Store unfollow failed', [
                'store_id' => $store->id,
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse(__('api.store_follow.unfollow_failed'), 500);
        }
    }

    /**
     * Toggle notification preference for a followed store.
     */
    public function toggleNotifications(Request $request, Store $store): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        if ($store->status !== StoreStatus::ACTIVE) {
            return $this->errorResponse(__('api.store_follow.store_not_found'), 404);
        }

        try {
            $follow = $this->toggleNotification->execute($store, $request->user());

            if (! $follow) {
                return $this->errorResponse(__('api.store_follow.not_following'), 422);
            }

            return $this->successResponse([
                'store_id' => $store->id,
                'notification_enabled' => (bool) $follow->notification_enabled,
            ], __('api.store_follow.notification_toggled'));
        } catch (\Throwable $e) {
            Log::error('Store follow notification toggle failed', [
                'store_id' => $store->id,
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse(__('api.store_follow.notification_toggle_failed'), 500);
        }
    }

    /**
     * List users who follow a specific store.
     */
    public function getFollowers(Request $request, string $store): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        $store = Store::query()->find($store);

        if (! $store) {
            return $this->errorResponse(__('api.store_follow.store_not_found'), 404);
        }

        if ($store->status !== StoreStatus::ACTIVE) {
            return $this->errorResponse(__('api.store_follow.store_not_found'), 404);
        }

        $validated = $request->validate([
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        try {
            $followers = $store->followerUsers()
                ->with('profile') // Eager load profile for name/avatar
                ->orderByPivot('followed_at', 'desc')
                ->paginate($validated['per_page'] ?? 15);

            return $this->paginatedResponse(
                StoreFollowerResource::collection($followers->getCollection())->resolve($request),
                __('api.store_follow.followers_retrieved'),
                $followers
            );
        } catch (\Throwable $e) {
            Log::error('Failed to retrieve store followers', ['store_id' => $store->id, 'error' => $e->getMessage()]);

            return $this->errorResponse(__('api.store_follow.followers_retrieve_failed'), 500);
        }
    }

    /**
     * List stores the authenticated user follows.
     */
    public function index(Request $request): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        $validated = $request->validate([
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        try {
            $stores = $request->user()
                ->followedStores()
                ->where('status', StoreStatus::ACTIVE)
                ->with(['categories', 'addresses', 'hours', 'socials.social'])
                ->orderByPivot('followed_at', 'desc')
                ->paginate($validated['per_page'] ?? 15);

            return $this->paginatedResponse(
                FollowedStoreResource::collection($stores->getCollection())->resolve($request),
                __('api.store_follow.followed_stores_retrieved'),
                $stores
            );
        } catch (\Throwable $e) {
            Log::error('Failed to retrieve followed stores', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse(__('api.store_follow.followed_stores_retrieve_failed'), 500);
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

    private function errorResponse(string $message, int $status = 400, ?array $errors = null): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors) {
            $response['errors'] = $errors;
        }

        return $this->localizedJson($response, $status);
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
