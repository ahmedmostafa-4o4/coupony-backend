<?php

namespace App\Application\Http\Controllers\API\V1;

use App\Application\Http\Controllers\Controller;
use App\Application\Http\Resources\ProductCollection;
use App\Domain\Product\Services\ProductRecommendationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductRecommendationController extends Controller
{
    public function __construct(
        private readonly ProductRecommendationService $recommendations,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        $validated = $request->validate([
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
            'per_page' => ['prohibited'],
        ]);

        try {
            $products = $this->recommendations->recommendFor(
                $request->user(),
                $validated['limit'] ?? 5
            );

            return $this->localizedJson([
                'success' => true,
                'message' => __('api.product.recommendations_retrieved'),
                'data' => (new ProductCollection($products))->resolve($request),
            ]);
        } catch (\Throwable $throwable) {
            report($throwable);

            return $this->localizedJson([
                'success' => false,
                'message' => __('api.product.recommendations_retrieve_failed'),
            ], 500);
        }
    }
}
