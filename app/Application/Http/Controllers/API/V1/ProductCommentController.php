<?php

namespace App\Application\Http\Controllers\API\V1;

use App\Application\Http\Controllers\Controller;
use App\Application\Http\Resources\ProductCommentResource;
use App\Domain\Product\Enums\ProductApprovalStatus;
use App\Domain\Product\Enums\ProductStatus;
use App\Domain\Product\Models\Product;
use App\Domain\Product\Models\ProductComment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductCommentController extends Controller
{
    public function index(Request $request, Product $product): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        if (! $this->isPublicProduct($product)) {
            return $this->errorResponse(__('api.product.not_found'), 404);
        }

        $request->attributes->set('resolved_user', $this->resolveAuthenticatedUser($request));

        $validated = $request->validate([
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $comments = ProductComment::query()
            ->where('product_id', $product->id)
            ->topLevel()
            ->visible()
            ->with(['user.profile', 'visibleReplies'])
            ->withCount('likes')
            ->latest()
            ->paginate($validated['per_page'] ?? 15);

        return $this->paginatedResponse(
            ProductCommentResource::collection($comments->getCollection())->resolve($request),
            __('api.product.comments_retrieved'),
            $comments
        );
    }

    public function store(Request $request, Product $product): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        if (! $this->isPublicProduct($product)) {
            return $this->errorResponse(__('api.product.not_found'), 404);
        }

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'body' => ['required', 'string', 'min:1', 'max:5000'],
        ]);

        try {
            $comment = ProductComment::query()->create([
                'product_id' => $product->id,
                'user_id' => $request->user()->id,
                'review_user_id' => $request->user()->id,
                'rating' => $validated['rating'],
                'body' => $validated['body'],
            ]);

            return $this->successResponse(
                ProductCommentResource::make($comment->load('user.profile')->loadCount('likes'))->resolve($request),
                __('api.product.comment_created'),
                201
            );
        } catch (QueryException $throwable) {
            return $this->errorResponse(__('api.product.comment_duplicate'), 422);
        } catch (\Throwable $throwable) {
            return $this->errorResponse(__('api.product.comment_create_failed'), 500);
        }
    }

    public function reply(Request $request, Product $product, ProductComment $comment): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        if (! $this->isPublicProduct($product) || $comment->product_id !== $product->id || ! $comment->isVisible()) {
            return $this->errorResponse(__('api.product.comment_not_found'), 404);
        }

        $validated = $request->validate([
            'body' => ['required', 'string', 'min:1', 'max:5000'],
            'rating' => ['prohibited'],
        ]);

        try {
            $reply = ProductComment::query()->create([
                'product_id' => $product->id,
                'user_id' => $request->user()->id,
                'parent_id' => $comment->id,
                'body' => $validated['body'],
            ]);

            return $this->successResponse(
                ProductCommentResource::make($reply->load('user.profile')->loadCount('likes'))->resolve($request),
                __('api.product.reply_created'),
                201
            );
        } catch (\Throwable $throwable) {
            return $this->errorResponse(__('api.product.reply_create_failed'), 500);
        }
    }

    public function update(Request $request, ProductComment $comment): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        if ($request->user()?->id !== $comment->user_id || ! $comment->isVisible()) {
            return $this->errorResponse(__('api.product.comment_not_found'), 404);
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
            ProductCommentResource::make($comment->fresh()->load('user.profile', 'visibleReplies')->loadCount('likes'))->resolve($request),
            __('api.product.comment_updated')
        );
    }

    public function destroy(Request $request, ProductComment $comment): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        if (! $this->canManageComment($request, $comment)) {
            return $this->errorResponse(__('api.product.comment_not_found'), 404);
        }

        $comment->delete();

        return $this->successResponse(null, __('api.product.comment_deleted'));
    }

    public function hide(Request $request, ProductComment $comment): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        $user = $request->user();
        $isAdmin = clone $user;
        $isAdmin = $isAdmin->hasRole('admin');

        $comment->loadMissing('product.store');
        $isOwner = $comment->product?->store?->owner_user_id === $user->id;

        if (! $isAdmin && ! $isOwner) {
            return $this->errorResponse(__('api.common.unauthorized'), 403);
        }

        $comment->update([
            'status' => ProductComment::STATUS_HIDDEN,
            'hidden_at' => now(),
            'hidden_by' => $user->id,
        ]);

        return $this->successResponse(
            ProductCommentResource::make($comment->fresh()->load('user.profile')->loadCount('likes'))->resolve($request),
            __('api.product.comment_hidden')
        );
    }

    private function isPublicProduct(Product $product): bool
    {
        return $product->status === ProductStatus::ACTIVE
            && $product->approval_status === ProductApprovalStatus::APPROVED;
    }

    private function canManageComment(Request $request, ProductComment $comment): bool
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
