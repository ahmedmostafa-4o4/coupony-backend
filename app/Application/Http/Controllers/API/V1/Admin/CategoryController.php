<?php

namespace App\Application\Http\Controllers\API\V1\Admin;

use App\Application\Http\Controllers\Controller;
use App\Application\Http\Requests\CreateCategoryRequest;
use App\Application\Http\Requests\UpdateCategoryRequest;
use App\Application\Http\Resources\CategoryResource;
use App\Domain\Product\Actions\Admin\CreateCategoryAction;
use App\Domain\Product\Actions\Admin\DeleteCategoryAction;
use App\Domain\Product\Actions\Admin\UpdateCategoryAction;
use App\Domain\Product\DTOs\Admin\CategoryDTO;
use App\Domain\Product\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        $categories = Category::query()
            ->when(
                $request->query('search'),
                fn ($query, $search) => $query->where(function ($q) use ($search) {
                    $q->where('name_en', 'like', "%{$search}%")
                      ->orWhere('name_ar', 'like', "%{$search}%");
                })
            )
            ->when(
                $request->query('active') === '1',
                fn ($query) => $query->where('is_active', true)
            )
            ->when(
                $request->query('active') === '0',
                fn ($query) => $query->where('is_active', false)
            )
            ->with('parent')
            ->orderBy('sort_order')
            ->orderBy('name_en')
            ->orderBy('name_ar')
            ->paginate($request->integer('per_page', 15));

        return $this->localizedJson([
            'message' => __('api.categories.retrieved'),
            'data' => CategoryResource::collection($categories)->response()->getData(true),
        ]);
    }

    public function show(Category $category): JsonResponse
    {
        $category->load('parent');

        return $this->localizedJson([
            'message' => __('api.categories.retrieved'),
            'data' => new CategoryResource($category),
        ]);
    }

    public function store(CreateCategoryRequest $request, CreateCategoryAction $action): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        $dto = CategoryDTO::fromRequest($request->validated());
        $category = $action->execute($dto);

        return $this->localizedJson([
            'message' => __('api.categories.created'),
            'data' => new CategoryResource($category),
        ], 201);
    }

    public function update(UpdateCategoryRequest $request, Category $category, UpdateCategoryAction $action): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        $dto = CategoryDTO::fromRequest($request->validated());
        $updatedCategory = $action->execute($category, $dto);

        return $this->localizedJson([
            'message' => __('api.categories.updated'),
            'data' => new CategoryResource($updatedCategory),
        ]);
    }

    public function destroy(Category $category, DeleteCategoryAction $action): JsonResponse
    {
        $action->execute($category);

        return $this->localizedJson([
            'message' => __('api.categories.deleted'),
        ]);
    }
}
