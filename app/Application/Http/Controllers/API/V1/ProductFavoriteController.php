<?php

namespace App\Application\Http\Controllers\API\V1;

use App\Application\Http\Controllers\Controller;
use App\Application\Http\Resources\PublicProductCollection;
use App\Domain\Product\Actions\FavoriteProduct;
use App\Domain\Product\Actions\UnfavoriteProduct;
use App\Domain\Product\Enums\ProductApprovalStatus;
use App\Domain\Product\Enums\ProductStatus;
use App\Domain\Product\Models\Product;
use App\Domain\Product\Repositories\ProductRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductFavoriteController extends Controller
{
    public function __construct(
        private readonly FavoriteProduct $favoriteProduct,
        private readonly UnfavoriteProduct $unfavoriteProduct,
        private readonly ProductRepository $products,
    ) {
    }

    public function store(Request $request, Product $product): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        if (!$this->isPubliclyFavoritable($product)) {
            return $this->errorResponse(__('api.product.not_found'), 404);
        }

        try {
            $this->favoriteProduct->execute($product, $request->user());

            return $this->successResponse([
                'product_id' => $product->id,
                'is_favorited' => true,
            ], __('api.product.favorited'));
        } catch (\Throwable $throwable) {
            report($throwable);

            return $this->errorResponse(__('api.product.favorite_failed'), 500);
        }
    }

    public function destroy(Request $request, Product $product): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        if (!$this->isPubliclyFavoritable($product)) {
            return $this->errorResponse(__('api.product.not_found'), 404);
        }

        try {
            $this->unfavoriteProduct->execute($product, $request->user());

            return $this->successResponse([
                'product_id' => $product->id,
                'is_favorited' => false,
            ], __('api.product.unfavorited'));
        } catch (\Throwable $throwable) {
            report($throwable);

            return $this->errorResponse(__('api.product.unfavorite_failed'), 500);
        }
    }

    public function index(Request $request): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        $validated = $request->validate([
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        try {
            $products = $this->products->favoriteProductsPaginate(
                $request->user(),
                $validated['per_page'] ?? 15
            );

            return $this->paginatedResponse(
                (new PublicProductCollection($products->getCollection()))->resolve($request),
                __('api.product.favorite_products_retrieved'),
                $products
            );
        } catch (\Throwable $throwable) {
            report($throwable);

            return $this->errorResponse(__('api.product.favorite_products_retrieve_failed'), 500);
        }
    }

    private function isPubliclyFavoritable(Product $product): bool
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
