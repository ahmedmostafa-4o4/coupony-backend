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
use Illuminate\Support\Facades\Storage;
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
                ->orderBy('name_en')
                ->orderBy('name_ar')
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
                'name' => $data['name_en'],
                'name_ar' => $data['name_ar'],
                'name_en' => $data['name_en'],
                'slug' => $data['slug'] ?? Str::slug($data['name_en']),
                'description' => $data['description'] ?? null,
                'parent_id' => $data['parent_id'] ?? null,
                'sort_order' => $data['sort_order'] ?? 0,
                'is_active' => $data['is_active'] ?? true,
            ]);

            if ($request->hasFile('icon')) {
                $category->update([
                    'icon_url' => $request->file('icon')->store("categories/{$category->id}/icon", 'public'),
                ]);
            }

            return $this->localizedJson([
                'message' => __('api.categories.created'),
                'data' => new CategoryResource($category->fresh()),
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

            if (array_key_exists('name_en', $data)) {
                $data['name'] = $data['name_en'];
            } elseif (array_key_exists('name_ar', $data) && blank($category->name_en)) {
                $data['name'] = $data['name_ar'];
            }

            if (!array_key_exists('slug', $data) && blank($category->slug)) {
                $slugSource = $data['name_en'] ?? $data['name_ar'] ?? null;

                if (filled($slugSource)) {
                    $data['slug'] = Str::slug($slugSource);
                }
            }

            unset($data['icon']);
            $category->update($data);

            if ($request->hasFile('icon')) {
                $oldIconPath = $category->icon_url;
                $newIconPath = $request->file('icon')->store("categories/{$category->id}/icon", 'public');
                $category->update(['icon_url' => $newIconPath]);
                $this->deleteStoredIconIfExists($oldIconPath);
            }

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

    private function deleteStoredIconIfExists(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
