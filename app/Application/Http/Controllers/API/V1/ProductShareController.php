<?php

namespace App\Application\Http\Controllers\API\V1;

use App\Application\Http\Controllers\Controller;
use App\Domain\Analytics\Services\AnalyticsCache;
use App\Domain\Product\Models\Product;
use App\Domain\Product\Models\ProductShare;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductShareController extends Controller
{
    /**
     * Record a product share event.
     */
    public function store(Request $request, Product $product): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        $validated = $request->validate([
            'platform' => ['nullable', 'string', 'in:whatsapp,facebook,twitter,instagram,copy_link,other'],
        ]);

        ProductShare::create([
            'product_id' => $product->id,
            'user_id' => $request->user()->id,
            'platform' => $validated['platform'] ?? null,
        ]);

        AnalyticsCache::invalidateProduct($product->id);
        AnalyticsCache::invalidateSeller($product->store_id);

        return $this->localizedJson(['message' => __('api.product.share_recorded')], 201);
    }
}
