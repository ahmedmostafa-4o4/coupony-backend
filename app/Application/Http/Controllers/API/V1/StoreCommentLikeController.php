<?php

namespace App\Application\Http\Controllers\API\V1;

use App\Application\Http\Controllers\Controller;
use App\Domain\Store\Enums\StoreStatus;
use App\Domain\Store\Models\StoreComment;
use App\Domain\Store\Models\StoreCommentLike;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StoreCommentLikeController extends Controller
{
    public function store(Request $request, StoreComment $comment): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        if (! $this->isLikeable($comment)) {
            return $this->errorResponse(__('api.store_comment.comment_not_found'), 404);
        }

        StoreCommentLike::query()->firstOrCreate([
            'comment_id' => $comment->id,
            'user_id' => $request->user()->id,
        ]);

        return $this->successResponse([
            'comment_id' => $comment->id,
            'likes_count' => $comment->likes()->count(),
            'is_liked' => true,
        ], __('api.store_comment.comment_liked'));
    }

    public function destroy(Request $request, StoreComment $comment): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        if (! $this->isLikeable($comment)) {
            return $this->errorResponse(__('api.store_comment.comment_not_found'), 404);
        }

        StoreCommentLike::query()
            ->where('comment_id', $comment->id)
            ->where('user_id', $request->user()->id)
            ->delete();

        return $this->successResponse([
            'comment_id' => $comment->id,
            'likes_count' => $comment->likes()->count(),
            'is_liked' => false,
        ], __('api.store_comment.comment_unliked'));
    }

    private function successResponse(mixed $data, string $message, int $status = 200): JsonResponse
    {
        return $this->localizedJson([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    private function errorResponse(string $message, int $status = 400): JsonResponse
    {
        return $this->localizedJson([
            'success' => false,
            'message' => $message,
        ], $status);
    }

    private function isLikeable(StoreComment $comment): bool
    {
        $comment->loadMissing('store');

        return $comment->isVisible()
            && $comment->store?->status === StoreStatus::ACTIVE;
    }
}
