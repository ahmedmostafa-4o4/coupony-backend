<?php

namespace App\Application\Http\Controllers\API\V1;

use App\Application\Http\Controllers\Controller;
use App\Application\Http\Requests\ExploreBootstrapRequest;
use App\Application\Http\Requests\ExplorePicksRequest;
use App\Domain\Explore\Actions\GetExploreBootstrapAction;
use App\Domain\Explore\Actions\GetExplorePicksAction;
use App\Domain\User\Models\User;
use Illuminate\Http\JsonResponse;

class ExploreController extends Controller
{
    public function __construct(
        private readonly GetExploreBootstrapAction $bootstrapAction,
        private readonly GetExplorePicksAction $picksAction,
    ) {}

    /**
     * Get the explore page bootstrap data.
     *
     * Returns all explore sections (interests, activities, trending,
     * flash, top_stores, nearby, server_time) in a single response.
     */
    public function bootstrap(ExploreBootstrapRequest $request): JsonResponse
    {
        /** @var User|null $user */
        $user = $this->resolveAuthenticatedUser($request);

        $result = $this->bootstrapAction->execute(
            $request->validated(),
            $user
        );

        return response()->json($result);
    }

    /**
     * Get the paginated "Picked for You" offers.
     *
     * Returns personalized recommendations for authenticated users,
     * or popular offers for guests, with filtering and sorting support.
     */
    public function picks(ExplorePicksRequest $request): JsonResponse
    {
        /** @var User|null $user */
        $user = $this->resolveAuthenticatedUser($request);

        $result = $this->picksAction->execute($request, $user);

        return response()->json($result);
    }
}
