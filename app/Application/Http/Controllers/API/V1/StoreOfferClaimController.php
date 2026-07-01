<?php

namespace App\Application\Http\Controllers\API\V1;

use App\Application\Http\Controllers\Controller;
use App\Application\Http\Requests\RedeemOfferClaimRequest;
use App\Application\Http\Resources\OfferClaimResource;
use App\Domain\Product\Actions\RedeemOfferClaim;
use App\Domain\Product\Enums\OfferClaimStatus;
use App\Domain\Product\Models\OfferClaim;
use App\Domain\Product\Repositories\ProductRepository;
use App\Domain\Store\Models\Store;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class StoreOfferClaimController extends Controller
{
    public function __construct(
        private readonly RedeemOfferClaim $redeemOfferClaim,
        private readonly ProductRepository $products,
    ) {}

    public function index(Request $request, Store $store): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);
        Gate::authorize('accessClaims', $store);

        $validated = $request->validate([
            'status' => ['nullable', 'in:'.implode(',', OfferClaimStatus::values())],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $claims = OfferClaim::query()
            ->withRedeemedUsageCount()
            ->where('store_id', $store->id)
            ->with(['user.profile', 'store', 'product.images', 'offer'])
            ->when(
                filled($validated['status'] ?? null),
                fn ($query) => $query->where('status', $validated['status'])
            )
            ->latest('redeemed_at')
            ->latest('created_at')
            ->paginate($validated['per_page'] ?? 15);

        return $this->localizedJson([
            'success' => true,
            'data' => OfferClaimResource::collection($claims->getCollection())->resolve($request),
            'meta' => [
                'current_page' => $claims->currentPage(),
                'last_page' => $claims->lastPage(),
                'per_page' => $claims->perPage(),
                'total' => $claims->total(),
            ],
        ]);
    }

    public function show(Request $request, Store $store, OfferClaim $claim): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);
        Gate::authorize('accessClaims', $store);

        if ($claim->store_id !== $store->id) {
            abort(404);
        }

        return $this->localizedJson([
            'success' => true,
            'data' => new OfferClaimResource($this->products->loadOfferClaim($claim)),
        ]);
    }

    public function redeem(RedeemOfferClaimRequest $request, Store $store): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);
        Gate::authorize('redeemClaims', $store);

        try {
            $claim = $this->redeemOfferClaim->execute(
                $store,
                $request->string('qr_code_token')->toString(),
                $request->user()
            );

            return $this->localizedJson([
                'success' => true,
                'message' => 'Offer claim redeemed successfully.',
                'data' => new OfferClaimResource($this->products->loadOfferClaim($claim)),
            ]);
        } catch (\DomainException $exception) {
            return $this->localizedJson([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        } catch (\Throwable $throwable) {
            return $this->localizedJson([
                'success' => false,
                'message' => 'Unable to redeem the offer claim.',
            ], 500);
        }
    }
}
