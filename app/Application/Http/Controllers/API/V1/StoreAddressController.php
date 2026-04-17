<?php

namespace App\Application\Http\Controllers\API\V1;

use App\Application\Http\Controllers\Controller;
use App\Application\Http\Requests\StoreAddressStoreRequest;
use App\Application\Http\Requests\StoreAddressUpdateRequest;
use App\Application\Http\Resources\StoreAddressResource;
use App\Domain\Store\Actions\CreateStoreAddress;
use App\Domain\Store\Actions\DeleteStoreAddress;
use App\Domain\Store\Actions\ListStoreAddresses;
use App\Domain\Store\Actions\UpdateStoreAddress;
use App\Domain\Store\Models\Store;
use App\Domain\User\Models\Address;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class StoreAddressController extends Controller
{
    public function __construct(
        private readonly ListStoreAddresses $listStoreAddresses,
        private readonly CreateStoreAddress $createStoreAddress,
        private readonly UpdateStoreAddress $updateStoreAddress,
        private readonly DeleteStoreAddress $deleteStoreAddress,
    ) {
    }

    public function index(Request $request, Store $store): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);
        Gate::authorize('manageAddresses', $store);

        return $this->localizedJson([
            'success' => true,
            'message' => 'Store addresses retrieved successfully.',
            'data' => StoreAddressResource::collection($this->listStoreAddresses->execute($store)),
        ]);
    }

    public function store(StoreAddressStoreRequest $request, Store $store): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);
        Gate::authorize('manageAddresses', $store);

        $address = DB::transaction(fn () => $this->createStoreAddress->execute($store, $request->validated()));

        return $this->localizedJson([
            'success' => true,
            'message' => 'Store address created successfully.',
            'data' => new StoreAddressResource($address),
        ], 201);
    }

    public function show(Request $request, Store $store, Address $address): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);
        Gate::authorize('manageAddresses', $store);

        return $this->localizedJson([
            'success' => true,
            'message' => 'Store address retrieved successfully.',
            'data' => new StoreAddressResource($address),
        ]);
    }

    public function update(StoreAddressUpdateRequest $request, Store $store, Address $address): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);
        Gate::authorize('manageAddresses', $store);

        $updatedAddress = DB::transaction(
            fn () => $this->updateStoreAddress->execute($store, $address, $request->validated())
        );

        return $this->localizedJson([
            'success' => true,
            'message' => 'Store address updated successfully.',
            'data' => new StoreAddressResource($updatedAddress),
        ]);
    }

    public function destroy(Request $request, Store $store, Address $address): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);
        Gate::authorize('manageAddresses', $store);

        DB::transaction(fn () => $this->deleteStoreAddress->execute($store, $address));

        return $this->localizedJson([
            'success' => true,
            'message' => 'Store address deleted successfully.',
        ]);
    }
}
