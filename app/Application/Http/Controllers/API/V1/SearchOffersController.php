<?php

namespace App\Application\Http\Controllers\API\V1;

use App\Application\Http\Controllers\Controller;
use App\Application\Http\Requests\SearchOffersRequest;
use App\Application\Http\Resources\SearchOfferResource;
use App\Domain\Search\Actions\ToggleOfferFavoriteAction;
use App\Domain\Search\Services\SearchOfferService;
use App\Domain\Product\Models\Product;
use Illuminate\Http\JsonResponse;

class SearchOffersController extends Controller
{
    public function __construct(
        private readonly SearchOfferService $searchOfferService,
        private readonly ToggleOfferFavoriteAction $toggleFavoriteAction
    ) {}

    /**
     * Search and filter offers based on complex criteria.
     */
    public function index(SearchOffersRequest $request): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);
        $user = $this->resolveAuthenticatedUser($request);

        $result = $this->searchOfferService->search($request->validated(), $user);

        // Map items via Resource
        $result['data']['items'] = SearchOfferResource::collection($result['data']['items'])
                                        ->resolve($request);

        return response()->json($result);
    }

    /**
     * Toggle favorite for an offer (product).
     */
    public function toggleFavorite(string $offerId): JsonResponse
    {
        $user = request()->user();
        
        $product = Product::findOrFail($offerId);

        $isFavorite = $this->toggleFavoriteAction->execute($product, $user);

        return response()->json([
            'success' => true,
            'message' => 'Favorite updated',
            'data' => [
                'offer_id' => $product->id,
                'product_id' => $product->id,
                'is_favorite' => $isFavorite,
            ],
        ]);
    }
}
