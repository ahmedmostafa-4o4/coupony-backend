<?php

namespace App\Application\Http\Controllers\API\V1;

use App\Application\Http\Controllers\Controller;
use App\Application\Http\Requests\CreateProductImageRequest;
use App\Application\Http\Requests\ReorderProductImagesRequest;
use App\Application\Http\Resources\ProductImageResource;
use App\Domain\Product\Actions\AddProductImages;
use App\Domain\Product\Actions\DeleteProductImage;
use App\Domain\Product\Actions\ReorderProductImages;
use App\Domain\Product\Actions\SetPrimaryProductImage;
use App\Domain\Product\Models\Product;
use App\Domain\Product\Models\ProductImage;
use App\Domain\Store\Models\Store;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ProductImageController extends Controller
{
    public function __construct(
        private readonly AddProductImages $addProductImagesAction,
        private readonly DeleteProductImage $deleteProductImageAction,
        private readonly ReorderProductImages $reorderProductImagesAction,
        private readonly SetPrimaryProductImage $setPrimaryProductImageAction,
    ) {}

    public function index(Request $request, Store $store, Product $product): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);
        Gate::authorize('view', $product);

        return $this->successResponse(
            ProductImageResource::collection($product->images()->get())->resolve($request),
            __('api.images.retrieved')
        );
    }

    public function store(CreateProductImageRequest $request, Store $store, Product $product): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);
        Gate::authorize('update', $product);

        try {
            $images = $this->addProductImagesAction->execute($product, $request->validated('images'));

            return $this->successResponse(
                ProductImageResource::collection($images)->resolve($request),
                __('api.images.created'),
                201
            );
        } catch (\Throwable $throwable) {
            return $this->errorResponse(__('api.images.create_failed'), 500);
        }
    }

    public function destroy(Request $request, Store $store, Product $product, ProductImage $image): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);
        Gate::authorize('delete', $product);

        try {
            $this->deleteProductImageAction->execute($product, $image);

            return $this->successResponse(null, __('api.images.deleted'));
        } catch (\Throwable $throwable) {
            return $this->errorResponse(__('api.images.delete_failed'), 500);
        }
    }

    public function reorder(ReorderProductImagesRequest $request, Store $store, Product $product): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);
        Gate::authorize('update', $product);

        try {
            $images = $this->reorderProductImagesAction->execute($product, $request->validated('images'));

            return $this->successResponse(
                ProductImageResource::collection($images)->resolve($request),
                __('api.images.reordered')
            );
        } catch (\Throwable $throwable) {
            return $this->errorResponse(__('api.images.reorder_failed'), 500);
        }
    }

    public function setPrimary(Request $request, Store $store, Product $product, ProductImage $image): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);
        Gate::authorize('update', $product);

        try {
            $image = $this->setPrimaryProductImageAction->execute($product, $image);

            return $this->successResponse(
                new ProductImageResource($image),
                __('api.images.primary_updated')
            );
        } catch (\Throwable $throwable) {
            return $this->errorResponse(__('api.images.primary_update_failed'), 500);
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
}
