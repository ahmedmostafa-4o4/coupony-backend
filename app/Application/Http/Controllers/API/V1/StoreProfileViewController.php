<?php

namespace App\Application\Http\Controllers\API\V1;

use App\Application\Http\Controllers\Controller;
use App\Domain\Store\Models\Store;
use App\Domain\Store\Models\StoreProfileView;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StoreProfileViewController extends Controller
{
    /**
     * Record a store profile view event.
     */
    public function store(Request $request, Store $store): JsonResponse
    {
        StoreProfileView::create([
            'store_id' => $store->id,
            'user_id' => $request->user()->id,
            'ip_address' => $request->ip(),
        ]);

        return response()->json(['message' => 'Profile view recorded.'], 201);
    }
}
