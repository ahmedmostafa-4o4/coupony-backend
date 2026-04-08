<?php

namespace App\Application\Http\Controllers\API\V1;

use App\Application\Http\Controllers\Controller;
use App\Application\Http\Requests\CreateProductVariantRequest;
use App\Application\Http\Requests\ReplaceVariantAttributesRequest;
use App\Application\Http\Requests\UpdateProductVariantRequest;
use App\Application\Http\Resources\ProductVariantResource;
use App\Domain\Product\Actions\CreateProductVariant;
use App\Domain\Product\Actions\DeleteProductVariant;
use App\Domain\Product\Actions\ReplaceVariantAttributes;
use App\Domain\Product\Actions\UpdateProductVariant;
use App\Domain\Product\Models\Product;
use App\Domain\Product\Models\ProductVariant;
use App\Domain\Product\Repositories\ProductRepository;
use App\Domain\Store\Models\Store;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ProductVariantController extends Controller
{
    public function __construct(
        private readonly CreateProductVariant $createVariantAction,
        private readonly UpdateProductVariant $updateVariantAction,
        private readonly DeleteProductVariant $deleteVariantAction,
        private readonly ReplaceVariantAttributes $replaceVariantAttributesAction,
        private readonly ProductRepository $products,
    ) {
    }

    public function index(Request $request, Store $store, Product $product): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);
        Gate::authorize('view', $product);

        return $this->successResponse(
            ProductVariantResource::collection($product->variants()->with('attributes')->get())->resolve($request),
            __('api.variants.retrieved')
        );
    }

    public function store(CreateProductVariantRequest $request, Store $store, Product $product): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);
        Gate::authorize('update', $product);

        try {
            $variant = $this->createVariantAction->execute($product, $request->validated());

            return $this->successResponse(
                new ProductVariantResource($variant),
                __('api.variants.created'),
                201
            );
        } catch (\Throwable $throwable) {
            return $this->errorResponse(__('api.variants.create_failed'), 500);
        }
    }

    public function show(Request $request, Store $store, Product $product, ProductVariant $variant): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);
        Gate::authorize('view', $product);

        return $this->successResponse(
            new ProductVariantResource($this->products->loadVariant($variant)),
            __('api.variants.details_retrieved')
        );
    }

    public function update(
        UpdateProductVariantRequest $request,
        Store $store,
        Product $product,
        ProductVariant $variant
    ): JsonResponse {
        $this->applyAuthenticatedLocale($request);
        Gate::authorize('update', $product);

        try {
            $variant = $this->updateVariantAction->execute($product, $variant, $request->validated());

            return $this->successResponse(
                new ProductVariantResource($variant),
                __('api.variants.updated')
            );
        } catch (\Throwable $throwable) {
            return $this->errorResponse(__('api.variants.update_failed'), 500);
        }
    }

    public function destroy(Request $request, Store $store, Product $product, ProductVariant $variant): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);
        Gate::authorize('delete', $product);

        try {
            $this->deleteVariantAction->execute($product, $variant);

            return $this->successResponse(null, __('api.variants.deleted'));
        } catch (\Throwable $throwable) {
            return $this->errorResponse(__('api.variants.delete_failed'), 500);
        }
    }

    public function replaceAttributes(
        ReplaceVariantAttributesRequest $request,
        Store $store,
        Product $product,
        ProductVariant $variant
    ): JsonResponse {
        $this->applyAuthenticatedLocale($request);
        Gate::authorize('update', $product);

        try {
            $variant = $this->replaceVariantAttributesAction->execute($variant, $request->validated('attributes'));

            return $this->successResponse(
                new ProductVariantResource($variant),
                __('api.attributes.updated')
            );
        } catch (\Throwable $throwable) {
            return $this->errorResponse(__('api.attributes.update_failed'), 500);
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
