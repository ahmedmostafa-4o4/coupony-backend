<?php

namespace App\Application\Http\Controllers\API\V1\Admin;

use App\Application\Http\Controllers\Controller;
use App\Application\Http\Requests\AdminListProductsRequest;
use App\Application\Http\Requests\AdminStoreProductRequest;
use App\Application\Http\Requests\AdminUpdateProductRequest;
use App\Application\Http\Resources\ProductCollection;
use App\Application\Http\Resources\ProductResource;
use App\Domain\Product\Actions\CreateAdminProduct;
use App\Domain\Product\Actions\DeleteAdminProduct;
use App\Domain\Product\Actions\ListAdminProducts;
use App\Domain\Product\Actions\UpdateAdminProduct;
use App\Domain\Product\DTOs\ProductData;
use App\Domain\Product\Models\Product;
use App\Domain\Product\Repositories\ProductRepository;
use App\Domain\Store\Models\Store;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Log;

class ProductManagementController extends Controller
{
    public function __construct(
        private readonly ListAdminProducts $listAdminProducts,
        private readonly CreateAdminProduct $createAdminProduct,
        private readonly UpdateAdminProduct $updateAdminProduct,
        private readonly DeleteAdminProduct $deleteAdminProduct,
        private readonly ProductRepository $products,
    ) {
    }

    public function index(AdminListProductsRequest $request): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        $products = $this->listAdminProducts->execute(
            $request->validated(),
            $request->integer('per_page', 15)
        );

        return $this->paginatedResponse(
            (new ProductCollection($products->getCollection()))->resolve($request),
            __('api.product.retrieved'),
            $products
        );
    }

    public function show(Request $request, Product $product): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);
        Gate::authorize('view', $product);

        return $this->successResponse(
            new ProductResource($this->products->loadAdminProduct($product)),
            __('api.product.details_retrieved')
        );
    }

    public function store(AdminStoreProductRequest $request): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        /** @var Store $store */
        $store = Store::query()->findOrFail($request->validated('store_id'));
        Gate::authorize('create', [Product::class, $store]);

        try {
            $product = $this->createAdminProduct->execute($store, ProductData::fromRequest($request), $request->user());

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

    public function update(AdminUpdateProductRequest $request, Product $product): JsonResponse
    {
        Log::info('FILES', $_FILES);
        $this->applyAuthenticatedLocale($request);
        Gate::authorize('update', $product);

        try {
            $updatedProduct = $this->updateAdminProduct->execute($product, ProductData::fromRequest($request), $request->user());

            return $this->successResponse(
                new ProductResource($updatedProduct),
                __('api.product.updated')
            );
        } catch (\InvalidArgumentException | \DomainException $throwable) {
            return $this->errorResponse($throwable->getMessage(), 422);
        } catch (\Throwable $throwable) {
            \Illuminate\Support\Facades\Log::error($throwable);
            return $this->errorResponse(__('api.product.update_failed'), 500);
        }
    }

    public function destroy(Request $request, Product $product): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);
        Gate::authorize('delete', $product);

        try {
            $this->deleteAdminProduct->execute($product);

            return $this->successResponse(null, __('api.product.deleted'));
        } catch (\Throwable $throwable) {
            return $this->errorResponse(__('api.product.delete_failed'), 500);
        }
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
