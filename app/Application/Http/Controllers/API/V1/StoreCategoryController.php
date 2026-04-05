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
            $category = StoreCategory::create($data);
            
            return response()->json([
                'message' => __('api.store_categories.created'),
                'data' => new StoreCategoryResource($category),
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
            $category->update($data);
            
            return response()->json([
                'message' => __('api.store_categories.updated'),
                'data' => new StoreCategoryResource($category),
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
}
