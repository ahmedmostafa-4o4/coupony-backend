<?php

namespace App\Application\Http\Controllers\API\V1;

use App\Application\Http\Controllers\Controller;
use App\Application\Http\Resources\NewFollowerResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MyFollowersController extends Controller
{
    /**
     * Get new followers for the authenticated seller's stores.
     *
     * GET /api/v1/me/followers/new
     */
    public function newFollowers(Request $request): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        $validated = $request->validate([
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $user = $request->user();
        $perPage = $validated['per_page'] ?? 20;

        // Get all stores owned by this user
        $storeIds = $user->stores()->pluck('id');

        if ($storeIds->isEmpty()) {
            return $this->localizedJson([
                'data' => [],
                'meta' => [
                    'current_page' => 1,
                    'last_page' => 1,
                    'per_page' => $perPage,
                    'total' => 0,
                ],
            ]);
        }

        // Get followers across all owned stores, ordered by most recent
        $followers = \App\Domain\User\Models\User::query()
            ->join('store_followers', 'users.id', '=', 'store_followers.user_id')
            ->whereIn('store_followers.store_id', $storeIds)
            ->with('profile')
            ->select('users.*')
            ->selectRaw('store_followers.followed_at as pivot_followed_at')
            ->orderByDesc('store_followers.followed_at')
            ->paginate($perPage);

        // Map to resource with pivot data
        $collection = $followers->getCollection()->map(function ($user) {
            $user->setRelation('pivot', (object) ['followed_at' => $user->pivot_followed_at]);

            return $user;
        });

        return $this->localizedJson([
            'data' => NewFollowerResource::collection($collection),
            'meta' => [
                'current_page' => $followers->currentPage(),
                'last_page' => $followers->lastPage(),
                'per_page' => $followers->perPage(),
                'total' => $followers->total(),
            ],
        ]);
    }
}
