<?php

namespace App\Application\Http\Controllers\API\V1;

use App\Application\Http\Controllers\Controller;
use App\Application\Http\Resources\StoreCommentResource;
use App\Domain\Store\Enums\StoreStatus;
use App\Domain\Store\Models\Store;
use App\Domain\Store\Models\StoreComment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StoreCommentController extends Controller
{
    public function index(Request $request, Store $store): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        if (! $this->isPublicStore($store)) {
            return $this->errorResponse(__('api.store_comment.store_not_found'), 404);
        }

        $request->attributes->set('resolved_user', $this->resolveAuthenticatedUser($request));

        $validated = $request->validate([
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $comments = StoreComment::query()
            ->where('store_id', $store->id)
            ->topLevel()
            ->visible()
            ->with(['user.profile', 'visibleReplies'])
            ->withCount('likes')
            ->latest()
            ->paginate($validated['per_page'] ?? 15);

        return $this->paginatedResponse(
            StoreCommentResource::collection($comments->getCollection())->resolve($request),
            __('api.store_comment.comments_retrieved'),
            $comments
        );
    }

    public function summary(Request $request, Store $store): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        if (! $this->isPublicStore($store)) {
            return $this->errorResponse(__('api.store_comment.store_not_found'), 404);
        }

        $distribution = StoreComment::query()
            ->where('store_id', $store->id)
            ->topLevel()
            ->visible()
            ->whereNotNull('rating')
            ->selectRaw('rating, COUNT(*) as total')
            ->groupBy('rating')
            ->pluck('total', 'rating');

        $counts = [
            5 => (int) ($distribution[5] ?? 0),
            4 => (int) ($distribution[4] ?? 0),
            3 => (int) ($distribution[3] ?? 0),
            2 => (int) ($distribution[2] ?? 0),
            1 => (int) ($distribution[1] ?? 0),
        ];

        return $this->successResponse([
            'avg_rating' => round((float) ($store->rating_avg ?? 0), 2),
            'rating_count' => (int) ($store->rating_count ?? 0),
            'five_star_count' => $counts[5],
            'four_star_count' => $counts[4],
            'three_star_count' => $counts[3],
            'two_star_count' => $counts[2],
            'one_star_count' => $counts[1],
            'ratings_breakdown' => [
                '5' => $counts[5],
                '4' => $counts[4],
                '3' => $counts[3],
                '2' => $counts[2],
                '1' => $counts[1],
            ],
        ], __('api.store_comment.comments_retrieved'));
    }

    public function store(Request $request, Store $store): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        if (! $this->isPublicStore($store)) {
            return $this->errorResponse(__('api.store_comment.store_not_found'), 404);
        }

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'body' => ['nullable', 'string', 'min:1', 'max:5000'],
        ]);

        try {
            $comment = StoreComment::query()->create([
                'store_id' => $store->id,
                'user_id' => $request->user()->id,
                'review_user_id' => $request->user()->id,
                'rating' => $validated['rating'],
                'body' => $validated['body'],
            ]);

            return $this->successResponse(
                StoreCommentResource::make($comment->load('user.profile')->loadCount('likes'))->resolve($request),
                __('api.store_comment.comment_created'),
                201
            );
        } catch (QueryException $throwable) {
            return $this->errorResponse(__('api.store_comment.comment_duplicate'), 422);
        } catch (\Throwable $throwable) {
            return $this->errorResponse(__('api.store_comment.comment_create_failed'), 500);
        }
    }

    public function reply(Request $request, Store $store, StoreComment $comment): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        if (! $this->isPublicStore($store) || $comment->store_id !== $store->id || ! $comment->isVisible()) {
            return $this->errorResponse(__('api.store_comment.comment_not_found'), 404);
        }

        $validated = $request->validate([
            'body' => ['required', 'string', 'min:1', 'max:5000'],
            'rating' => ['prohibited'],
        ]);

        try {
            $reply = StoreComment::query()->create([
                'store_id' => $store->id,
                'user_id' => $request->user()->id,
                'parent_id' => $comment->id,
                'body' => $validated['body'],
            ]);

            return $this->successResponse(
                StoreCommentResource::make($reply->load('user.profile')->loadCount('likes'))->resolve($request),
                __('api.store_comment.reply_created'),
                201
            );
        } catch (\Throwable $throwable) {
            return $this->errorResponse(__('api.store_comment.reply_create_failed'), 500);
        }
    }

    public function update(Request $request, StoreComment $comment): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        if ($request->user()?->id !== $comment->user_id || ! $comment->isVisible()) {
            return $this->errorResponse(__('api.store_comment.comment_not_found'), 404);
        }

        $rules = [
            'body' => ['sometimes', 'required', 'string', 'min:1', 'max:5000'],
        ];

        if ($comment->isTopLevelReview()) {
            $rules['rating'] = ['sometimes', 'required', 'integer', 'min:1', 'max:5'];
        } else {
            $rules['rating'] = ['prohibited'];
        }

        $validated = $request->validate($rules);

        $comment->update($validated);

        return $this->successResponse(
            StoreCommentResource::make($comment->fresh()->load('user.profile', 'visibleReplies')->loadCount('likes'))->resolve($request),
            __('api.store_comment.comment_updated')
        );
    }

    public function destroy(Request $request, StoreComment $comment): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        if (! $this->canManageComment($request, $comment)) {
            return $this->errorResponse(__('api.store_comment.comment_not_found'), 404);
        }

        $comment->delete();

        return $this->successResponse(null, __('api.store_comment.comment_deleted'));
    }

    public function hide(Request $request, StoreComment $comment): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        $user = $request->user();
        $isAdmin = clone $user;
        $isAdmin = $isAdmin->hasRole('admin');

        $comment->loadMissing('store');
        $isOwner = $comment->store?->owner_user_id === $user->id;

        if (! $isAdmin && ! $isOwner) {
            return $this->errorResponse(__('api.common.unauthorized'), 403);
        }

        $comment->update([
            'status' => StoreComment::STATUS_HIDDEN,
            'hidden_at' => now(),
            'hidden_by' => $user->id,
        ]);

        return $this->successResponse(
            StoreCommentResource::make($comment->fresh()->load('user.profile')->loadCount('likes'))->resolve($request),
            __('api.store_comment.comment_hidden')
        );
    }

    private function isPublicStore(Store $store): bool
    {
        return $store->status === StoreStatus::ACTIVE;
    }

    private function canManageComment(Request $request, StoreComment $comment): bool
    {
        return $request->user()?->id === $comment->user_id
            || (bool) $request->user()?->hasRole('admin');
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
