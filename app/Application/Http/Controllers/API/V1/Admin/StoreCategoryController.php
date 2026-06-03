<?php

namespace App\Application\Http\Controllers\API\V1\Admin;

use App\Application\Http\Controllers\Controller;
use App\Application\Http\Requests\createStoreCategoryRequest;
use App\Application\Http\Requests\updateStoreCategoryRequest;
use App\Application\Http\Resources\StoreCategoryResource;
use App\Domain\Store\Actions\Admin\CreateStoreCategoryAction;
use App\Domain\Store\Actions\Admin\DeleteStoreCategoryAction;
use App\Domain\Store\Actions\Admin\UpdateStoreCategoryAction;
use App\Domain\Store\DTOs\Admin\StoreCategoryDTO;
use App\Domain\Store\Models\StoreCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StoreCategoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        $query = StoreCategory::query()
            ->when(
                $request->query('search'),
                fn ($q, $search) => $q->where(function ($sq) use ($search) {
                    $sq->where('name_en', 'like', "%{$search}%")
                       ->orWhere('name_ar', 'like', "%{$search}%");
                })
            )
            ->when(
                $request->query('active') !== null,
                fn ($q) => $q->where('is_active', $request->query('active') === '1')
            );

        $categories = $query->paginate($request->integer('per_page', 15));

        return $this->localizedJson([
            'message' => __('api.store_categories.retrieved'),
            'data' => StoreCategoryResource::collection($categories)->response()->getData(true),
        ]);
    }

    public function show(StoreCategory $category): JsonResponse
    {
        return $this->localizedJson([
            'message' => __('api.store_categories.retrieved'),
            'data' => new StoreCategoryResource($category),
        ]);
    }

    public function store(createStoreCategoryRequest $request, CreateStoreCategoryAction $action): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        $dto = StoreCategoryDTO::fromRequest($request->validated());
        $category = $action->execute($dto);

        return $this->localizedJson([
            'message' => __('api.store_categories.created'),
            'data' => new StoreCategoryResource($category),
        ], 201);
    }

    public function update(updateStoreCategoryRequest $request, StoreCategory $category, UpdateStoreCategoryAction $action): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        $dto = StoreCategoryDTO::fromRequest($request->validated());
        $updatedCategory = $action->execute($category, $dto);

        return $this->localizedJson([
            'message' => __('api.store_categories.updated'),
            'data' => new StoreCategoryResource($updatedCategory),
        ]);
    }

    public function destroy(StoreCategory $category, DeleteStoreCategoryAction $action): JsonResponse
    {
        $action->execute($category);

        return $this->localizedJson([
            'message' => __('api.store_categories.deleted'),
        ]);
    }
}
