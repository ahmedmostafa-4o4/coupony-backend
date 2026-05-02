<?php

namespace App\Application\Http\Controllers\API\V1;

use App\Application\Http\Controllers\Controller;
use App\Application\Http\Requests\CreateProductRequest;
use App\Application\Http\Requests\UpdateProductRequest;
use App\Application\Http\Requests\UpdateProductStatusRequest;
use App\Application\Http\Resources\CategoryResource;
use App\Application\Http\Resources\ProductCollection;
use App\Application\Http\Resources\ProductResource;
use App\Domain\Product\Actions\CreateProduct;
use App\Domain\Product\Actions\DeleteProduct;
use App\Domain\Product\Actions\UpdateProduct;
use App\Domain\Product\Actions\UpdateProductStatus;
use App\Domain\Product\DTOs\ProductData;
use App\Domain\Product\Enums\ProductApprovalStatus;
use App\Domain\Product\Enums\ProductStatus;
use App\Domain\Product\Models\Category;
use App\Domain\Product\Models\Product;
use App\Domain\Product\Repositories\ProductRepository;
use App\Domain\Store\Models\Store;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Log;

class ProductController extends Controller
{
    public function __construct(
        private readonly CreateProduct $createProductAction,
        private readonly UpdateProduct $updateProductAction,
        private readonly DeleteProduct $deleteProductAction,
        private readonly UpdateProductStatus $updateProductStatusAction,
        private readonly ProductRepository $products,
    ) {
    }

    public function store(CreateProductRequest $request, Store $store): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);
        Gate::authorize('create', [Product::class, $store]);

        try {
            $product = $this->createProductAction->execute($store, ProductData::fromRequest($request), $request->user());

            return $this->successResponse(
                new ProductResource($product),
                __('api.product.created'),
                201
            );
        } catch (\InvalidArgumentException | \DomainException $throwable) {
            return $this->errorResponse($throwable->getMessage(), 422);
        } catch (\Throwable $throwable) {
            return $this->errorResponse(__('api.product.create_failed'), 500);
        }
    }

    public function sellerIndex(Request $request, Store $store): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);
        Gate::authorize('viewAny', [Product::class, $store]);

        $validated = $request->validate([
            'status' => ['nullable', Rule::in(ProductStatus::values())],
            'search' => ['nullable', 'string', 'max:255'],
            'is_featured' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        try {
            $products = $this->products->sellerPaginate(
                $store,
                [...$validated, 'liked_by_user' => $request->user()],
                $validated['per_page'] ?? 15
            );

            return $this->paginatedResponse(
                (new ProductCollection($products->getCollection()))->resolve($request),
                __('api.product.retrieved'),
                $products
            );
        } catch (\Throwable $throwable) {
            return $this->errorResponse(__('api.product.retrieve_failed'), 500);
        }
    }

    public function show(Request $request, Store $store, Product $product): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);
        Gate::authorize('view', $product);

        try {
            return $this->successResponse(
                new ProductResource($this->products->loadSellerProduct($product, $request->user())),
                __('api.product.details_retrieved')
            );
        } catch (\Throwable $throwable) {
            return $this->errorResponse(__('api.product.retrieve_failed'), 500);
        }
    }

    public function update(UpdateProductRequest $request, Store $store, Product $product): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);
        Gate::authorize('update', $product);
        try {
            $updatedProduct = $this->updateProductAction->execute($product, ProductData::fromRequest($request), $request->user());
            return $this->successResponse(
                new ProductResource($updatedProduct),
                __('api.product.updated')
            );
        } catch (\InvalidArgumentException | \DomainException $throwable) {
            return $this->errorResponse($throwable->getMessage(), 422);
        } catch (\Throwable $throwable) {
            return $this->errorResponse(__('api.product.update_failed'), 500);
        }
    }

    public function destroy(Request $request, Store $store, Product $product): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);
        Gate::authorize('delete', $product);

        try {
            $this->deleteProductAction->execute($product);

            return $this->successResponse(
                null,
                __('api.product.deleted')
            );
        } catch (\Throwable $throwable) {
            return $this->errorResponse(__('api.product.delete_failed'), 500);
        }
    }

    public function updateStatus(
        UpdateProductStatusRequest $request,
        Store $store,
        Product $product
    ): JsonResponse {
        $this->applyAuthenticatedLocale($request);
        Gate::authorize('updateStatus', $product);

        try {
            $updatedProduct = $this->updateProductStatusAction->execute(
                $product,
                $request->string('status')->toString()
            );

            return $this->successResponse(
                new ProductResource($this->products->loadSellerProduct($updatedProduct, $request->user())),
                __('api.product.status_updated')
            );
        } catch (\Throwable $throwable) {
            return $this->errorResponse(__('api.product.status_update_failed'), 500);
        }
    }

    public function publicIndex(Request $request): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        $validated = $request->validate([
            'category' => ['nullable', 'integer', 'exists:categories,id'],
            'search' => ['nullable', 'string', 'max:255'],
            'featured' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        try {
            $products = $this->products->publicPaginate(
                [...$validated, 'liked_by_user' => $this->resolveAuthenticatedUser($request)],
                $validated['per_page'] ?? 15
            );

            return $this->paginatedResponse(
                (new ProductCollection($products->getCollection()))->resolve($request),
                __('api.product.public_retrieved'),
                $products
            );
        } catch (\Throwable $throwable) {
            Log::error($throwable);
            return $this->errorResponse(__('api.product.public_retrieve_failed'), 500);
        }
    }

    public function publicShow(Request $request, Product $product): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        if (
            $product->status !== ProductStatus::ACTIVE
            || $product->approval_status !== ProductApprovalStatus::APPROVED
        ) {
            return $this->errorResponse(__('api.product.not_found'), 404);
        }

        try {
            return $this->successResponse(
                new ProductResource($this->products->loadPublicProduct($product, $this->resolveAuthenticatedUser($request))),
                __('api.product.public_details_retrieved')
            );
        } catch (\Throwable $throwable) {
            return $this->errorResponse(__('api.product.public_retrieve_failed'), 500);
        }
    }

    public function categories(Request $request): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        try {
            return $this->successResponse(
                CategoryResource::collection($this->products->publicCategories())->resolve($request),
                __('api.categories.retrieved')
            );
        } catch (\Throwable $throwable) {
            return $this->errorResponse(__('api.categories.retrieve_failed'), 500);
        }
    }

    public function categoryProducts(Request $request, Category $category): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        if (!$category->is_active) {
            return $this->errorResponse(__('api.categories.not_found'), 404);
        }

        $validated = $request->validate([
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        try {
            $products = $this->products->publicCategoryProductsPaginate(
                $category,
                $validated['per_page'] ?? 15,
                $this->resolveAuthenticatedUser($request)
            );

            return $this->paginatedResponse(
                (new ProductCollection($products->getCollection()))->resolve($request),
                __('api.product.public_retrieved'),
                $products
            );
        } catch (\Throwable $throwable) {
            return $this->errorResponse(__('api.product.public_retrieve_failed'), 500);
        }
    }

    private function successResponse($data, string $message, int $status = 200): JsonResponse
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

    private function paginatedResponse($data, string $message, LengthAwarePaginator $paginator): JsonResponse
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
