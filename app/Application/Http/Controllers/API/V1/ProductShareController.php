<?php

namespace App\Application\Http\Controllers\API\V1;

use App\Application\Http\Controllers\Controller;
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
        $validated = $request->validate([
            'platform' => ['nullable', 'string', 'in:whatsapp,facebook,twitter,instagram,copy_link,other'],
        ]);

        ProductShare::create([
            'product_id' => $product->id,
            'user_id' => $request->user()->id,
            'platform' => $validated['platform'] ?? null,
        ]);

        return response()->json(['message' => 'Share recorded.'], 201);
    }
}
