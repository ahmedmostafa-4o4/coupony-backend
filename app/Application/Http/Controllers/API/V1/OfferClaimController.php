<?php

namespace App\Application\Http\Controllers\API\V1;

use App\Application\Http\Controllers\Controller;
use App\Application\Http\Requests\CreateOfferClaimRequest;
use App\Application\Http\Resources\OfferClaimResource;
use App\Domain\Product\Actions\CreateOfferClaim;
use App\Domain\Product\Exceptions\OfferClaimLimitException;
use App\Domain\Product\Models\Product;
use Illuminate\Http\JsonResponse;

class OfferClaimController extends Controller
{
    public function __construct(private readonly CreateOfferClaim $createOfferClaim) {}

    public function store(CreateOfferClaimRequest $request, Product $product): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        try {
            $claim = $this->createOfferClaim->execute($product, $request->user(), $request->validated());

            return $this->localizedJson([
                'success' => true,
                'message' => __('api.offer_claim.created'),
                'data' => new OfferClaimResource($claim),
            ], 201);
        } catch (OfferClaimLimitException $exception) {
            return $this->localizedJson([
                'success' => false,
                'message' => $exception->getMessage(),
                'reason' => $exception->reason,
            ], 422);
        } catch (\DomainException $exception) {
            return $this->localizedJson([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        } catch (\Throwable $throwable) {
            return $this->localizedJson([
                'success' => false,
                'message' => __('api.offer_claim.create_failed'),
            ], 500);
        }
    }
}
