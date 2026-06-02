<?php

namespace App\Application\Http\Controllers\API\V1;

use App\Application\Http\Controllers\Controller;
use App\Application\Http\Requests\BannerShareRequest;
use App\Application\Http\Resources\BannerClaimResource;
use App\Application\Http\Resources\BannerResource;
use App\Domain\Banner\Models\Banner;
use App\Domain\Banner\Models\BannerShare;
use App\Domain\Banner\Services\BannerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerBannerController extends Controller
{
    public function __construct(private readonly BannerService $banners)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        $viewer = $this->resolveAuthenticatedUser($request);
        $banners = $this->banners->activeBanners($viewer);

        return $this->successResponse(
            BannerResource::collection($banners)->resolve($request),
            'Banners retrieved successfully.'
        );
    }

    public function show(Request $request, Banner $banner): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        $viewer = $this->resolveAuthenticatedUser($request);
        $banner = $this->banners->loadForCustomer($banner, $viewer);

        if (!$banner) {
            return $this->errorResponse('Banner not found.', 404);
        }

        return $this->successResponse(new BannerResource($banner), 'Banner details retrieved successfully.');
    }

    public function like(Request $request, Banner $banner): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        if (!$this->banners->loadForCustomer($banner, $request->user())) {
            return $this->errorResponse('Banner not found.', 404);
        }

        $banner = $this->banners->like($banner, $request->user());

        return $this->successResponse([
            'banner_id' => $banner->id,
            'likes_count' => (int) ($banner->likes_count ?? 0),
            'is_liked' => true,
        ], 'Banner liked successfully.');
    }

    public function unlike(Request $request, Banner $banner): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        if (!$this->banners->loadForCustomer($banner, $request->user())) {
            return $this->errorResponse('Banner not found.', 404);
        }

        $banner = $this->banners->unlike($banner, $request->user());

        return $this->successResponse([
            'banner_id' => $banner->id,
            'likes_count' => (int) ($banner->likes_count ?? 0),
            'is_liked' => false,
        ], 'Banner unliked successfully.');
    }

    public function favorite(Request $request, Banner $banner): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        if (!$this->banners->loadForCustomer($banner, $request->user())) {
            return $this->errorResponse('Banner not found.', 404);
        }

        $banner = $this->banners->favorite($banner, $request->user());

        return $this->successResponse([
            'banner_id' => $banner->id,
            'is_favorited' => true,
        ], 'Banner added to favorites successfully.');
    }

    public function unfavorite(Request $request, Banner $banner): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        if (!$this->banners->loadForCustomer($banner, $request->user())) {
            return $this->errorResponse('Banner not found.', 404);
        }

        $banner = $this->banners->unfavorite($banner, $request->user());

        return $this->successResponse([
            'banner_id' => $banner->id,
            'is_favorited' => false,
        ], 'Banner removed from favorites successfully.');
    }

    public function share(BannerShareRequest $request, Banner $banner): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        if (!$this->banners->loadForCustomer($banner, $request->user())) {
            return $this->errorResponse('Banner not found.', 404);
        }

        BannerShare::query()->create([
            'banner_id' => $banner->id,
            'user_id' => $request->user()?->id,
            'platform' => $request->validated('platform'),
        ]);

        return $this->localizedJson([
            'success' => true,
            'message' => 'Banner share recorded successfully.',
        ], 201);
    }

    public function claim(Request $request, Banner $banner): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        try {
            $claim = $this->banners->createClaim($banner, $request->user());

            return $this->localizedJson([
                'success' => true,
                'message' => 'Banner claim created successfully.',
                'data' => new BannerClaimResource($claim),
            ], 201);
        } catch (\DomainException $exception) {
            return $this->errorResponse($exception->getMessage(), 422);
        } catch (\Throwable $throwable) {
            report($throwable);

            return $this->errorResponse('Unable to create the banner claim.', 500);
        }
    }

    private function successResponse(mixed $data, string $message, int $status = 200): JsonResponse
    {
        return $this->localizedJson([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    private function errorResponse(string $message, int $status = 400): JsonResponse
    {
        return $this->localizedJson([
            'success' => false,
            'message' => $message,
        ], $status);
    }
}
