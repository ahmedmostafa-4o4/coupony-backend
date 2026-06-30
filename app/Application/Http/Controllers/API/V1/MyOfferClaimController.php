<?php

namespace App\Application\Http\Controllers\API\V1;

use App\Application\Http\Controllers\Controller;
use App\Application\Http\Resources\OfferClaimResource;
use App\Domain\Product\Enums\OfferClaimStatus;
use App\Domain\Product\Models\Category;
use App\Domain\Product\Models\OfferClaim;
use App\Domain\Product\Repositories\ProductRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

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
            'subcategory' => ['nullable', 'integer', 'exists:categories,id'],
            'category_slug' => ['nullable', 'string', 'max:255'],
            'sort_by' => ['nullable', Rule::in(['newest', 'expires_soon', 'status_then_discount'])],
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
            ->when(
                filled($validated['subcategory'] ?? null),
                function (Builder $q) use ($validated) {
                    $q->whereHas('product.categories', fn (Builder $cq) => $cq->where('categories.id', $validated['subcategory']));
                }
            )
            ->when(
                filled($validated['category_slug'] ?? null),
                function (Builder $q) use ($validated) {
                    $q->whereHas('product.categories', function (Builder $categoryQuery) use ($validated) {
                        $categoryQuery
                            ->where('categories.slug', $validated['category_slug'])
                            ->orWhereIn(
                                'categories.parent_id',
                                Category::query()
                                    ->where('slug', $validated['category_slug'])
                                    ->select('id')
                            );
                    });
                }
            );

        $this->applySort($query, $validated['sort_by'] ?? 'newest');

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

    private function applySort(Builder $query, string $sortBy): void
    {
        match ($sortBy) {
            'expires_soon' => $query
                ->orderByRaw('CASE WHEN status = ? THEN 0 ELSE 1 END', [OfferClaimStatus::ACTIVE->value])
                ->orderByRaw('CASE WHEN expires_at IS NULL THEN 1 ELSE 0 END')
                ->orderBy('expires_at')
                ->latest('created_at'),
            'status_then_discount' => $query
                ->orderByRaw('CASE WHEN status = ? THEN 0 ELSE 1 END', [OfferClaimStatus::ACTIVE->value])
                ->orderByDesc(DB::raw($this->discountSortExpression()))
                ->latest('created_at'),
            default => $query->latest('created_at'),
        };
    }

    private function discountSortExpression(): string
    {
        $driver = DB::connection()->getDriverName();

        return match ($driver) {
            'mysql', 'mariadb' => "COALESCE(
                CAST(JSON_UNQUOTE(JSON_EXTRACT(offer_snapshot, '$.offer.percentage_value')) AS DECIMAL(10,2)),
                CAST(JSON_UNQUOTE(JSON_EXTRACT(offer_snapshot, '$.offer.fixed_amount')) AS DECIMAL(10,2)),
                CAST(JSON_UNQUOTE(JSON_EXTRACT(offer_snapshot, '$.offer.max_discount')) AS DECIMAL(10,2)),
                0
            )",
            'sqlite' => "COALESCE(
                CAST(json_extract(offer_snapshot, '$.offer.percentage_value') AS REAL),
                CAST(json_extract(offer_snapshot, '$.offer.fixed_amount') AS REAL),
                CAST(json_extract(offer_snapshot, '$.offer.max_discount') AS REAL),
                0
            )",
            default => '0',
        };
    }
}
