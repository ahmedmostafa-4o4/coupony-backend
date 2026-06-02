<?php

namespace App\Domain\FollowingFeed\Actions;

use App\Application\Http\Resources\FollowingFeedItemResource;
use App\Domain\FollowingFeed\Services\FollowingFeedService;
use App\Domain\User\Models\User;

class GetFollowingFeedAction
{
    public function __construct(
        private readonly FollowingFeedService $followingFeedService,
    ) {}

    /**
     * Execute the following feed action.
     *
     * Orchestrates FollowingFeedService to fetch the paginated feed items
     * using the fallback algorithm (followed -> recommended -> trending).
     */
    public function execute(array $requestData, ?User $user): array
    {
        $page = (int) ($requestData['page'] ?? 1);
        $perPage = (int) ($requestData['per_page'] ?? 10);
        
        $lat = isset($requestData['latitude']) ? (float) $requestData['latitude'] : null;
        $lng = isset($requestData['longitude']) ? (float) $requestData['longitude'] : null;

        $paginator = $this->followingFeedService->getFeedItems($user, $page, $perPage, $lat, $lng);

        return [
            'success' => true,
            'data' => [
                'items' => FollowingFeedItemResource::collection($paginator->items())->resolve(),
                'pagination' => [
                    'current_page' => $paginator->currentPage(),
                    'per_page' => $paginator->perPage(),
                    'total_items' => $paginator->total(),
                    'total_pages' => $paginator->lastPage(),
                    'has_next_page' => $paginator->hasMorePages(),
                ],
            ],
        ];
    }
}
