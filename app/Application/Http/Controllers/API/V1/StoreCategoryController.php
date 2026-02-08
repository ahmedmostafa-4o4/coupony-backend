<?php

namespace App\Application\Http\Controllers\API\V1;

use App\Application\Http\Controllers\Controller;
use App\Application\Http\Requests\createStoreCategoryRequest;
use App\Application\Http\Requests\updateStoreCategoryRequest;
use App\Domain\Store\Models\StoreCategory;
use App\Mail\ContactUs;
use DB;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

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
        $categories = $request->query('active') == '1'
            ? StoreCategory::active()->get()
            : ($request->query('active') == '0'
                ? StoreCategory::inActive()->get()
                : StoreCategory::all());

        return response()->json($categories);
    }

    public function store(createStoreCategoryRequest $request)
    {
        $data = $request->validated();
        $category = StoreCategory::create($data);
        return response()->json($category);
    }

    public function update(updateStoreCategoryRequest $request, StoreCategory $category)
    {
        $data = $request->validated();
        $category->update($data);
        return response()->json($category);
    }

    public function destroy(StoreCategory $category)
    {
        $category->delete();
        return ['message' => 'Category deleted'];
    }
}
