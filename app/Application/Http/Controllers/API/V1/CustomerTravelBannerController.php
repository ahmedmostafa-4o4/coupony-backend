<?php

namespace App\Application\Http\Controllers\API\V1;

use App\Application\Http\Controllers\Controller;
use App\Domain\Banner\Models\TravelBanner;
use Illuminate\Http\JsonResponse;

class CustomerTravelBannerController extends Controller
{
    public function index(): JsonResponse
    {
        $banners = TravelBanner::where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('start_date')
                      ->orWhere('start_date', '<=', now());
            })
            ->where(function ($query) {
                $query->whereNull('end_date')
                      ->orWhere('end_date', '>=', now());
            })
            ->with(['product' => function ($query) {
                $query->active()->with(['images', 'offer']);
            }])
            ->orderBy('priority')
            ->latest()
            ->get()
            ->filter(function ($banner) {
                return $banner->product !== null; // Ensure the linked product is active
            })
            ->values();

        return response()->json([
            'success' => true,
            'message' => 'Travel banners retrieved successfully.',
            'data' => $banners->map(function ($banner) {
                return [
                    'id' => $banner->id,
                    'image_url' => $banner->image_url ? url('storage/' . $banner->image_url) : null,
                    'cta_text' => $banner->cta_text,
                    'save_percent' => $banner->save_percent,
                    'priority' => $banner->priority,
                    'start_date' => $banner->start_date,
                    'end_date' => $banner->end_date,
                    'product' => [
                        'id' => $banner->product->id,
                        'title' => $banner->product->title,
                        'base_price' => $banner->product->base_price,
                        'compare_at_price' => $banner->product->compare_at_price,
                        'rating_avg' => $banner->product->rating_avg,
                        'image' => $banner->product->images->firstWhere('is_primary', true)?->image_url,
                        'has_offer' => $banner->product->offer !== null,
                    ],
                ];
            }),
        ]);
    }
}
