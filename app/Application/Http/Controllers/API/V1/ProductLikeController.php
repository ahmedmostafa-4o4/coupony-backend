<?php

namespace App\Application\Http\Controllers\API\V1;

use App\Application\Http\Controllers\Controller;
use App\Application\Http\Resources\ProductCollection;
use App\Domain\Product\Actions\LikeProduct;
use App\Domain\Product\Actions\UnlikeProduct;
use App\Domain\Product\Enums\ProductApprovalStatus;
use App\Domain\Product\Enums\ProductStatus;
use App\Domain\Product\Models\Product;
use App\Domain\Product\Repositories\ProductRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductLikeController extends Controller
{
    public function __construct(
        private readonly LikeProduct $likeProduct,
        private readonly UnlikeProduct $unlikeProduct,
        private readonly ProductRepository $products,
    ) {}

    public function store(Request $request, Product $product): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        if (! $this->isPubliclyLikeable($product)) {
            return $this->errorResponse(__('api.product.not_found'), 404);
        }

        try {
            $this->likeProduct->execute($product, $request->user());
            $product = $this->products->loadPublicProduct($product->fresh(), $request->user());

            return $this->successResponse([
                'product_id' => $product->id,
                'likes_count' => (int) ($product->likes_count ?? 0),
                'is_liked' => true,
            ], __('api.product.liked'));
        } catch (\Throwable $throwable) {
            return $this->errorResponse(__('api.product.like_failed'), 500);
        }
    }

    public function destroy(Request $request, Product $product): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        if (! $this->isPubliclyLikeable($product)) {
            return $this->errorResponse(__('api.product.not_found'), 404);
        }

        try {
            $this->unlikeProduct->execute($product, $request->user());
            $product = $this->products->loadPublicProduct($product->fresh(), $request->user());

            return $this->successResponse([
                'product_id' => $product->id,
                'likes_count' => (int) ($product->likes_count ?? 0),
                'is_liked' => false,
            ], __('api.product.unliked'));
        } catch (\Throwable $throwable) {
            return $this->errorResponse(__('api.product.unlike_failed'), 500);
        }
    }

    public function index(Request $request): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        $validated = $request->validate([
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        try {
            $products = $this->products->likedProductsPaginate(
                $request->user(),
                $validated['per_page'] ?? 15
            );

            return $this->paginatedResponse(
                (new ProductCollection($products->getCollection()))->resolve($request),
                __('api.product.liked_products_retrieved'),
                $products
            );
        } catch (\Throwable $throwable) {
            return $this->errorResponse(__('api.product.liked_products_retrieve_failed'), 500);
        }
    }

    private function isPubliclyLikeable(Product $product): bool
    {
        return $product->status === ProductStatus::ACTIVE
            && $product->approval_status === ProductApprovalStatus::APPROVED;
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
