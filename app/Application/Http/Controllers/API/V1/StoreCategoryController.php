<?php

namespace App\Application\Http\Controllers\API\V1;

use App\Application\Http\Controllers\Controller;
use App\Application\Http\Requests\createStoreCategoryRequest;
use App\Application\Http\Requests\updateStoreCategoryRequest;
use App\Application\Http\Resources\StoreCategoryResource;
use App\Domain\Store\Models\StoreCategory;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class StoreCategoryController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            // examples with aliases, pipe-separated names, guards, etc:
            new Middleware(\Spatie\Permission\Middleware\RoleMiddleware::using('admin')),
            new Middleware('auth:sanctum'),
        ];
    }

    public function index(Request $request)
    {
        $this->applyAuthenticatedLocale($request);

        try {
            $categories = $request->query('active') == '1'
                ? StoreCategory::active()->get()
                : ($request->query('active') == '0'
                    ? StoreCategory::inActive()->get()
                    : StoreCategory::all());

            return response()->json([
                'message' => __('api.store_categories.retrieved'),
                'data' => StoreCategoryResource::collection($categories),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve store categories', ['error' => $e->getMessage()]);
            
            return response()->json([
                'message' => __('api.store_categories.retrieve_failed'),
            ], 500);
        }
    }

    public function store(createStoreCategoryRequest $request)
    {
        $this->applyAuthenticatedLocale($request);

        try {
            $data = $request->validated();
            unset($data['icon']);
            unset($data['image_category']);
            $category = StoreCategory::create($data);

            if ($request->hasFile('icon')) {
                $category->update([
                    'icon_url' => $request->file('icon')->store("store-categories/{$category->id}/icon", 'public'),
                ]);
            }

            if ($request->hasFile('image_category')) {
                $category->update([
                    'image_category' => $request->file('image_category')->store("store-categories/{$category->id}/image", 'public'),
                ]);
            }
            
            return response()->json([
                'message' => __('api.store_categories.created'),
                'data' => new StoreCategoryResource($category->fresh()),
            ], 201);
        } catch (\Exception $e) {
            Log::error('Failed to create store category', ['error' => $e->getMessage()]);
            
            return response()->json([
                'message' => __('api.store_categories.create_failed'),
            ], 500);
        }
    }

    public function update(updateStoreCategoryRequest $request, StoreCategory $category)
    {
        $this->applyAuthenticatedLocale($request);

        try {
            $data = $request->validated();
            unset($data['icon']);
            unset($data['image_category']);
            $category->update($data);

            if ($request->hasFile('icon')) {
                $oldIconPath = $category->icon_url;
                $newIconPath = $request->file('icon')->store("store-categories/{$category->id}/icon", 'public');
                $category->update(['icon_url' => $newIconPath]);
                $this->deleteStoredIconIfExists($oldIconPath);
            }

            if ($request->hasFile('image_category')) {
                $oldImageCategoryPath = $category->image_category;
                $newImageCategoryPath = $request->file('image_category')->store("store-categories/{$category->id}/image", 'public');
                $category->update(['image_category' => $newImageCategoryPath]);
                $this->deleteStoredIconIfExists($oldImageCategoryPath);
            }
            
            return response()->json([
                'message' => __('api.store_categories.updated'),
                'data' => new StoreCategoryResource($category->fresh()),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update store category', [
                'category_id' => $category->id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'message' => __('api.store_categories.update_failed'),
            ], 500);
        }
    }

    public function destroy(StoreCategory $category)
    {
        try {
            // Check if category has stores
            if ($category->stores()->exists()) {
                return response()->json([
                    'message' => __('api.store_categories.delete_blocked'),
                ], 400);
            }

            $this->deleteStoredIconIfExists($category->icon_url);
            $this->deleteStoredIconIfExists($category->image_category);
            $category->delete();
            
            return response()->json([
                'message' => __('api.store_categories.deleted'),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to delete store category', [
                'category_id' => $category->id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'message' => __('api.store_categories.delete_failed'),
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
