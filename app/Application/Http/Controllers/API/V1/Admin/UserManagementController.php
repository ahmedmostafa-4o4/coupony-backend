<?php

namespace App\Application\Http\Controllers\API\V1\Admin;

use App\Application\Http\Controllers\Controller;
use App\Application\Http\Requests\Admin\UserManagement\IndexUsersRequest;
use App\Application\Http\Requests\Admin\UserManagement\StoreUserRequest;
use App\Application\Http\Requests\Admin\UserManagement\UpdateUserPasswordRequest;
use App\Application\Http\Requests\Admin\UserManagement\UpdateUserRequest;
use App\Application\Http\Requests\Admin\UserManagement\UpdateUserStatusRequest;
use App\Application\Http\Resources\UserResource;
use App\Domain\User\Actions\Admin\DeleteUserAction;
use App\Domain\User\Actions\Admin\RevokeAllUserSessionsAction;
use App\Domain\User\Actions\Admin\RevokeUserSessionAction;
use App\Domain\User\Actions\Admin\StoreUserAction;
use App\Domain\User\Actions\Admin\UpdateUserAction;
use App\Domain\User\Actions\Admin\UpdateUserPasswordAction;
use App\Domain\User\Actions\Admin\UpdateUserStatusAction;
use App\Domain\User\DTOs\Admin\StoreUserDTO;
use App\Domain\User\DTOs\Admin\UpdateUserDTO;
use App\Domain\User\DTOs\Admin\UserFilterDTO;
use App\Domain\User\Models\User;
use App\Domain\User\Repositories\AdminUserRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UserManagementController extends Controller
{
    public function __construct(
        private readonly AdminUserRepository $repository,
        private readonly StoreUserAction $storeUserAction,
        private readonly UpdateUserAction $updateUserAction,
        private readonly UpdateUserStatusAction $updateUserStatusAction,
        private readonly UpdateUserPasswordAction $updateUserPasswordAction,
        private readonly DeleteUserAction $deleteUserAction,
        private readonly RevokeAllUserSessionsAction $revokeAllUserSessionsAction,
        private readonly RevokeUserSessionAction $revokeUserSessionAction
    ) {
    }

    public function index(IndexUsersRequest $request): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        try {
            $dto = UserFilterDTO::fromRequest($request);
            $paginator = $this->repository->paginateUsers($dto);

            return $this->localizedJson([
                'message' => __('api.admin.users.list_retrieved'),
                'data' => UserResource::collection($paginator->items()),
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve users list', ['error' => $e->getMessage()]);

            return $this->localizedJson([
                'message' => __('api.admin.users.list_failed'),
            ], 500);
        }
    }

    public function show(Request $request, User $user): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        try {
            $user->load(['profile', 'roles', 'points', 'stores', 'sessions']);
            return $this->localizedJson([
                'message' => __('api.admin.users.details_retrieved'),
                'data' => new UserResource($user),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve user details', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return $this->localizedJson([
                'message' => __('api.admin.users.details_failed'),
            ], 500);
        }
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        try {
            $dto = StoreUserDTO::fromRequest($request);
            $user = $this->storeUserAction->execute($dto);

            return $this->localizedJson([
                'message' => __('api.admin.users.created'),
                'data' => new UserResource($user),
            ], 201);
        } catch (\Exception $e) {
            Log::error('Failed to create user', [
                'error' => $e->getMessage(),
            ]);

            return $this->localizedJson([
                'message' => __('api.admin.users.create_failed'),
            ], 500);
        }
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        try {
            $dto = new UpdateUserDTO($request->validated());
            $user = $this->updateUserAction->execute($user, $dto);

            return $this->localizedJson([
                'message' => __('api.admin.users.updated'),
                'data' => new UserResource($user),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update user', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return $this->localizedJson([
                'message' => __('api.admin.users.update_failed'),
            ], 500);
        }
    }

    public function updateStatus(UpdateUserStatusRequest $request, User $user): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        try {
            $user = $this->updateUserStatusAction->execute($user, $request->validated('status'));

            return $this->localizedJson([
                'message' => __('api.admin.users.status_updated'),
                'data' => new UserResource($user),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update user status', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return $this->localizedJson([
                'message' => __('api.admin.users.status_update_failed'),
            ], 500);
        }
    }

    public function updatePassword(UpdateUserPasswordRequest $request, User $user): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        try {
            $this->updateUserPasswordAction->execute($user, $request->validated('password'));

            return $this->localizedJson([
                'message' => __('api.admin.users.password_updated'),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update user password', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return $this->localizedJson([
                'message' => __('api.admin.users.password_update_failed'),
            ], 500);
        }
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        if ($request->user()->id === $user->id) {
            return $this->localizedJson([
                'message' => __('api.admin.users.cannot_delete_self'),
            ], 400);
        }

        try {
            $this->deleteUserAction->execute($user);

            return $this->localizedJson([
                'message' => __('api.admin.users.deleted'),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to delete user', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return $this->localizedJson([
                'message' => __('api.admin.users.delete_failed'),
            ], 500);
        }
    }

    public function statistics(Request $request): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        try {
            $stats = $this->repository->getStatistics();

            return $this->localizedJson([
                'message' => __('api.admin.users.statistics_retrieved'),
                'data' => $stats,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve user statistics', ['error' => $e->getMessage()]);

            return $this->localizedJson([
                'message' => __('api.admin.users.statistics_failed'),
            ], 500);
        }
    }

    public function revokeAllSessions(Request $request, User $user): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        try {
            $this->revokeAllUserSessionsAction->execute($user);

            return $this->localizedJson([
                'message' => __('api.admin.users.sessions_revoked'),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to revoke all user sessions', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return $this->localizedJson([
                'message' => __('api.admin.users.sessions_revoke_failed'),
            ], 500);
        }
    }

    public function revokeSession(Request $request, User $user, string $sessionId): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        try {
            $this->revokeUserSessionAction->execute($user, $sessionId);

            return $this->localizedJson([
                'message' => __('api.admin.users.session_revoked'),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to revoke user session', [
                'user_id' => $user->id,
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
            ]);

            return $this->localizedJson([
                'message' => __('api.admin.users.session_revoke_failed'),
            ], 500);
        }
    }
}
