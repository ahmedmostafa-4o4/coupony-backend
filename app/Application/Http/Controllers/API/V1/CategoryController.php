<?php

namespace App\Application\Http\Controllers\API\V1;

use App\Application\Http\Controllers\Controller;
use App\Application\Http\Requests\CreateCategoryRequest;
use App\Application\Http\Requests\UpdateCategoryRequest;
use App\Application\Http\Resources\CategoryResource;
use App\Domain\Product\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CategoryController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware(\Spatie\Permission\Middleware\RoleMiddleware::using('admin')),
            new Middleware('auth:sanctum'),
        ];
    }

    public function index(Request $request): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        try {
            $categories = Category::query()
                ->when(
                    $request->query('active') === '1',
                    fn($query) => $query->where('is_active', true)
                )
                ->when(
                    $request->query('active') === '0',
                    fn($query) => $query->where('is_active', false)
                )
                ->with('parent')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get();

            return $this->localizedJson([
                'message' => __('api.categories.retrieved'),
                'data' => CategoryResource::collection($categories),
            ]);
        } catch (\Throwable $throwable) {
            Log::error('Failed to retrieve product categories', [
                'error' => $throwable->getMessage(),
            ]);

            return $this->localizedJson([
                'message' => __('api.categories.retrieve_failed'),
            ], 500);
        }
    }

    public function store(CreateCategoryRequest $request): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        try {
            $data = $request->validated();
            $category = Category::create([
                'name' => $data['name'],
                'slug' => $data['slug'] ?? Str::slug($data['name']),
                'description' => $data['description'] ?? null,
                'parent_id' => $data['parent_id'] ?? null,
                'sort_order' => $data['sort_order'] ?? 0,
                'is_active' => $data['is_active'] ?? true,
            ]);

            return $this->localizedJson([
                'message' => __('api.categories.created'),
                'data' => new CategoryResource($category),
            ], 201);
        } catch (\Throwable $throwable) {
            Log::error('Failed to create product category', [
                'error' => $throwable->getMessage(),
            ]);

            return $this->localizedJson([
                'message' => __('api.categories.create_failed'),
            ], 500);
        }
    }

    public function update(UpdateCategoryRequest $request, Category $category): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        try {
            $data = $request->validated();

            if (array_key_exists('name', $data) && !array_key_exists('slug', $data) && blank($category->slug)) {
                $data['slug'] = Str::slug($data['name']);
            }

            $category->update($data);

            return $this->localizedJson([
                'message' => __('api.categories.updated'),
                'data' => new CategoryResource($category->fresh()),
            ]);
        } catch (\Throwable $throwable) {
            Log::error('Failed to update product category', [
                'category_id' => $category->id,
                'error' => $throwable->getMessage(),
            ]);

            return $this->localizedJson([
                'message' => __('api.categories.update_failed'),
            ], 500);
        }
    }

    public function destroy(Category $category): JsonResponse
    {
        try {
            if ($category->products()->exists() || $category->children()->exists()) {
                return $this->localizedJson([
                    'message' => __('api.categories.delete_blocked'),
                ], 400);
            }

            $category->delete();

            return $this->localizedJson([
                'message' => __('api.categories.deleted'),
            ]);
        } catch (\Throwable $throwable) {
            Log::error('Failed to delete product category', [
                'category_id' => $category->id,
                'error' => $throwable->getMessage(),
            ]);

            return $this->localizedJson([
                'message' => __('api.categories.delete_failed'),
            ], 500);
        }
    }
}
