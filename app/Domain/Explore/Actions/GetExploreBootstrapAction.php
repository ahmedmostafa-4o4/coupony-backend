<?php

namespace App\Domain\Explore\Actions;

use App\Domain\Explore\Services\ExploreService;
use App\Domain\User\Models\User;
use Carbon\Carbon;

class GetExploreBootstrapAction
{

    public function __construct(
        private readonly ExploreService $exploreService,
    ) {}

    /**
     * Execute the explore bootstrap action.
     *
     * Orchestrates ExploreService to build the full bootstrap response
     * containing all explore page sections.
     *
     * @param array $requestData Validated request data (interest_id, activity_id, search, lat, lng)
     * @param User|null $user Optional authenticated user for is_favorite resolution
     * @return array
     */
    public function execute(array $requestData, ?User $user = null): array
    {
        $filters = [
            'interest_id' => $requestData['interest_id'] ?? null,
            'activity_id' => $requestData['activity_id'] ?? null,
            'search' => $requestData['search'] ?? null,
        ];

        $lat = $requestData['lat'] ?? null;
        $lng = $requestData['lng'] ?? null;

        // Build nearby section only if both lat and lng are provided
        $nearby = ($lat !== null && $lng !== null)
            ? $this->exploreService->getNearbyOffers($filters, $user, (float) $lat, (float) $lng)
            : [];

        return [
            'success' => true,
            'data' => [
                'interests' => $this->exploreService->getInterests($user),
                'activities' => $this->exploreService->getActivities(),
                'trending' => $this->exploreService->getTrendingOffers($filters, $user),
                'flash' => $this->exploreService->getFlashOffers($filters, $user),
                'top_stores' => $this->exploreService->getTopStores($filters),
                'nearby' => $nearby,
                'server_time' => Carbon::now('UTC')->format('Y-m-d\TH:i:s\Z'),
            ],
        ];
    }
}
