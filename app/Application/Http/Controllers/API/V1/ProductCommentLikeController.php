<?php

namespace App\Application\Http\Controllers\API\V1;

use App\Application\Http\Controllers\Controller;
use App\Domain\Product\Enums\ProductApprovalStatus;
use App\Domain\Product\Enums\ProductStatus;
use App\Domain\Product\Models\ProductComment;
use App\Domain\Product\Models\ProductCommentLike;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductCommentLikeController extends Controller
{
    public function store(Request $request, ProductComment $comment): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        if (! $this->isLikeable($comment)) {
            return $this->errorResponse(__('api.product.comment_not_found'), 404);
        }

        ProductCommentLike::query()->firstOrCreate([
            'comment_id' => $comment->id,
            'user_id' => $request->user()->id,
        ]);

        return $this->successResponse([
            'comment_id' => $comment->id,
            'likes_count' => $comment->likes()->count(),
            'is_liked' => true,
        ], __('api.product.comment_liked'));
    }

    public function destroy(Request $request, ProductComment $comment): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        if (! $this->isLikeable($comment)) {
            return $this->errorResponse(__('api.product.comment_not_found'), 404);
        }

        ProductCommentLike::query()
            ->where('comment_id', $comment->id)
            ->where('user_id', $request->user()->id)
            ->delete();

        return $this->successResponse([
            'comment_id' => $comment->id,
            'likes_count' => $comment->likes()->count(),
            'is_liked' => false,
        ], __('api.product.comment_unliked'));
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

    private function isLikeable(ProductComment $comment): bool
    {
        $comment->loadMissing('product');

        return $comment->isVisible()
            && $comment->product?->status === ProductStatus::ACTIVE
            && $comment->product?->approval_status === ProductApprovalStatus::APPROVED;
    }
}
