<?php

namespace App\Application\Http\Controllers\API\V1;

use App\Application\Http\Controllers\Controller;
use App\Application\Http\Requests\createStoreRequest;
use App\Domain\Store\DTOs\StoreData;
use App\Domain\Store\Services\StoreService;
use App\Domain\Store\Actions\CreateStore;
use Illuminate\Http\JsonResponse;

class StoreController extends Controller
{
    public function __construct(
        private StoreService $storeService,
        private CreateStore $createStore,
    ) {
    }
    public function create(createStoreRequest $request): JsonResponse
    {
        $user = $request->user();
        try {
            $store = $this->createStore->execute(
                $user,
                StoreData::fromRequest($request)
            );

            return response()->json([
                'message' => 'Store created successfully. Pending approval.',
                'data' => [
                    'store' => $store->load('verifications'),
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create store',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}