<?php

namespace App\Application\Http\Controllers\API\V1;

use App\Application\Http\Controllers\Controller;
use App\Application\Http\Requests\FollowingFeedRequest;
use App\Domain\FollowingFeed\Actions\GetFollowingFeedAction;
use App\Domain\User\Models\User;
use Illuminate\Http\JsonResponse;

class FollowingFeedController extends Controller
{
    public function __construct(
        private readonly GetFollowingFeedAction $getFollowingFeedAction,
    ) {}

    /**
     * Get the following feed with pagination.
     */
    public function index(FollowingFeedRequest $request): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        /** @var User|null $user */
        $user = $request->user('sanctum');

        $result = $this->getFollowingFeedAction->execute($request->validated(), $user);

        return $this->localizedJson($result);
    }
}
