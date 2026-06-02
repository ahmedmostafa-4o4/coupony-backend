<?php

namespace App\Application\Http\Controllers\API\V1\Admin;

use App\Application\Http\Controllers\Controller;
use App\Application\Http\Requests\AdminTravelBannerRequest;
use App\Application\Http\Requests\AdminTravelBannerSelectableProductsRequest;
use App\Application\Http\Requests\AdminUpdateTravelBannerRequest;
use App\Domain\Banner\Models\TravelBanner;
use App\Domain\Product\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class TravelBannerController extends Controller
{
    public function index(): JsonResponse
    {
        $banners = TravelBanner::with('product')
            ->orderBy('priority')
            ->latest()
            ->paginate(15);

        return response()->json([
            'success' => true,
            'message' => 'Travel banners retrieved successfully.',
            'data' => $banners->items(),
            'meta' => [
                'current_page' => $banners->currentPage(),
                'last_page' => $banners->lastPage(),
                'per_page' => $banners->perPage(),
                'total' => $banners->total(),
            ]
        ]);
    }

    public function store(AdminTravelBannerRequest $request): JsonResponse
    {
        $validated = $request->validated();
        
        $path = $request->file('image')->store('travel_banners', 'public');
        $validated['image_url'] = $path;

        $banner = TravelBanner::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Travel banner created successfully.',
            'data' => $banner->load('product')
        ], 201);
    }

    public function show(TravelBanner $travelBanner): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Travel banner retrieved successfully.',
            'data' => $travelBanner->load('product')
        ]);
    }

    public function update(AdminUpdateTravelBannerRequest $request, TravelBanner $travelBanner): JsonResponse
    {
        $validated = $request->validated();

        if ($request->hasFile('image')) {
            if ($travelBanner->image_url) {
                Storage::disk('public')->delete($travelBanner->image_url);
            }
            $path = $request->file('image')->store('travel_banners', 'public');
            $validated['image_url'] = $path;
        }

        $travelBanner->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Travel banner updated successfully.',
            'data' => $travelBanner->fresh()->load('product')
        ]);
    }

    public function destroy(TravelBanner $travelBanner): JsonResponse
    {
        if ($travelBanner->image_url) {
            Storage::disk('public')->delete($travelBanner->image_url);
        }
        
        $travelBanner->delete();

        return response()->json([
            'success' => true,
            'message' => 'Travel banner deleted successfully.'
        ]);
    }

    public function selectableProducts(AdminTravelBannerSelectableProductsRequest $request): JsonResponse
    {
        $query = Product::query()
            ->active()
            ->with(['images', 'offer'])
            ->withCount('likes');

        if ($request->filled('category_id')) {
            $query->whereHas('categories', function ($q) use ($request) {
                $q->where('categories.id', $request->category_id);
            });
        }

        if ($request->filled('min_review_score')) {
            $query->where('rating_avg', '>=', $request->min_review_score);
        }

        if ($request->filled('min_price')) {
            $query->where('base_price', '>=', $request->min_price);
        }

        if ($request->filled('max_price')) {
            $query->where('base_price', '<=', $request->max_price);
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                  ->orWhere('short_description', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('sort_by')) {
            match ($request->sort_by) {
                'most_likes' => $query->orderByDesc('likes_count'),
                'most_saves' => $query->orderByDesc('favorites_count'),
                'price_asc' => $query->orderBy('base_price'),
                'price_desc' => $query->orderByDesc('base_price'),
                'newest' => $query->latest(),
                default => $query->latest()
            };
        } else {
            $query->latest();
        }

        $products = $query->paginate($request->integer('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => 'Selectable products retrieved successfully.',
            'data' => collect($products->items())->map(function ($product) {
                return [
                    'id' => $product->id,
                    'title' => $product->title,
                    'base_price' => $product->base_price,
                    'rating_avg' => $product->rating_avg,
                    'favorites_count' => $product->favorites_count,
                    'likes_count' => $product->likes_count,
                    'image' => $product->images->firstWhere('is_primary', true)?->image_url,
                    'has_offer' => $product->offer !== null,
                ];
            }),
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ]
        ]);
    }
}
