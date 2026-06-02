<?php

namespace App\Domain\Explore\Actions;

use App\Application\Http\Requests\ExplorePicksRequest;
use App\Domain\Explore\Services\ExploreService;
use App\Domain\User\Models\User;

class GetExplorePicksAction
{
    public function __construct(
        private readonly ExploreService $exploreService,
    ) {}

    /**
     * Execute the action to get paginated picked-for-you offers.
     *
     * Extracts filters and pagination from the validated request,
     * delegates to ExploreService for data retrieval, and builds
     * the response with pagination metadata.
     */
    public function execute(ExplorePicksRequest $request, ?User $user): array
    {
        $filters = [
            'interest_id' => $request->validated('interest_id'),
            'activity_id' => $request->validated('activity_id'),
            'search' => $request->validated('search'),
            'min_discount_percent' => $request->validated('min_discount_percent'),
            'sort_by' => $request->validated('sort_by'),
        ];

        $page = (int) ($request->validated('page') ?? 1);
        $pageSize = (int) ($request->validated('page_size') ?? 12);

        $paginator = $this->exploreService->getPickedOffers($filters, $user, $page, $pageSize);

        $total = $paginator->total();
        $totalPages = (int) ceil($total / $pageSize);
        $hasMore = $page < $totalPages;

        return [
            'success' => true,
            'data' => $paginator->items(),
            'pagination' => [
                'page' => $page,
                'page_size' => $pageSize,
                'total' => $total,
                'total_pages' => $totalPages,
                'has_more' => $hasMore,
            ],
        ];
    }
}
