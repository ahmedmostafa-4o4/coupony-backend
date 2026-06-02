<?php

namespace App\Application\Http\Controllers\API\V1;

use App\Application\Http\Controllers\Controller;
use App\Application\Http\Resources\OfferClaimResource;
use App\Domain\Product\Enums\OfferClaimStatus;
use App\Domain\Product\Models\OfferClaim;
use App\Domain\Product\Repositories\ProductRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MyOfferClaimController extends Controller
{
    public function __construct(
        private readonly ProductRepository $products,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        $validated = $request->validate([
            'status' => ['nullable', 'in:'.implode(',', OfferClaimStatus::values())],
            'search' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'integer', 'exists:categories,id'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = OfferClaim::query()
            ->where('user_id', $request->user()->id)
            ->with(['store', 'product', 'offer'])
            ->when(
                filled($validated['status'] ?? null),
                fn (Builder $q) => $q->where('status', $validated['status'])
            )
            ->when(
                filled($validated['search'] ?? null),
                function (Builder $q) use ($validated) {
                    $search = '%'.$validated['search'].'%';
                    $q->where(function (Builder $subQuery) use ($search) {
                        $subQuery->whereHas('product', fn (Builder $pq) => $pq->where('title', 'like', $search))
                            ->orWhereHas('store', fn (Builder $sq) => $sq->where('name', 'like', $search))
                            ->orWhere('claim_token', 'like', $search);
                    });
                }
            )
            ->when(
                filled($validated['category'] ?? null),
                function (Builder $q) use ($validated) {
                    $q->whereHas('product.categories', fn (Builder $cq) => $cq->where('categories.id', $validated['category']));
                }
            )
            ->latest('created_at');

        $claims = $query->paginate($validated['per_page'] ?? 15);

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

    public function show(Request $request, OfferClaim $claim): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        if ($claim->user_id !== $request->user()->id) {
            abort(404);
        }

        return $this->localizedJson([
            'success' => true,
            'data' => new OfferClaimResource($this->products->loadOfferClaim($claim)),
        ]);
    }
}
