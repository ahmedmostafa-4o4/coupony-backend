<?php

namespace App\Application\Http\Controllers\API\V1;

use App\Application\Http\Controllers\Controller;
use App\Application\Http\Resources\ProductRevisionResource;
use App\Domain\Product\Models\Product;
use App\Domain\Product\Models\ProductRevision;
use App\Domain\Product\Repositories\ProductRepository;
use App\Domain\Store\Models\Store;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ProductRevisionController extends Controller
{
    public function __construct(private readonly ProductRepository $products)
    {
    }

    public function index(Request $request, Store $store, Product $product): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);
        Gate::authorize('view', $product);

        $revisions = $this->products->sellerRevisionPaginate($product, $request->integer('per_page', 15));

        return $this->localizedJson([
            'success' => true,
            'data' => ProductRevisionResource::collection($revisions->getCollection())->resolve($request),
            'meta' => [
                'current_page' => $revisions->currentPage(),
                'last_page' => $revisions->lastPage(),
                'per_page' => $revisions->perPage(),
                'total' => $revisions->total(),
            ],
        ]);
    }

    public function show(Request $request, Store $store, Product $product, ProductRevision $revision): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);
        Gate::authorize('view', $product);

        if ($revision->product_id !== $product->id) {
            abort(404);
        }

        return $this->localizedJson([
            'success' => true,
            'data' => new ProductRevisionResource($this->products->loadRevision($revision)),
        ]);
    }
}
