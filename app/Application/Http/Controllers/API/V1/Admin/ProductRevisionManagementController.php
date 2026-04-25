<?php

namespace App\Application\Http\Controllers\API\V1\Admin;

use App\Application\Http\Controllers\Controller;
use App\Application\Http\Resources\ProductResource;
use App\Application\Http\Resources\ProductRevisionResource;
use App\Domain\Product\Actions\ApproveProductRevision;
use App\Domain\Product\Actions\RejectProductRevision;
use App\Domain\Product\Enums\ProductRevisionStatus;
use App\Domain\Product\Models\ProductRevision;
use App\Domain\Product\Repositories\ProductRepository;
use App\Domain\Product\Support\ProductReviewFields;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductRevisionManagementController extends Controller
{
    public function __construct(
        private readonly ProductRepository $products,
        private readonly ApproveProductRevision $approveRevision,
        private readonly RejectProductRevision $rejectRevision,
    ) {}

    public function pending(Request $request): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        $revisions = $this->products->pendingRevisionsPaginate($request->integer('per_page', 15));

        return $this->localizedJson([
            'success' => true,
            'data' => ProductRevisionResource::collection($revisions->getCollection())->resolve($request),
            'meta' => [
                'current_page' => $revisions->currentPage(),
                'last_page' => $revisions->lastPage(),
                'per_page' => $revisions->perPage(),
                'total' => $revisions->total(),
            ],
        ]);
    }

    public function show(Request $request, ProductRevision $revision): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        return $this->localizedJson([
            'success' => true,
            'data' => new ProductRevisionResource($this->products->loadRevision($revision)),
        ]);
    }

    public function approve(Request $request, ProductRevision $revision): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($revision->status !== ProductRevisionStatus::PENDING) {
            return $this->localizedJson([
                'success' => false,
                'message' => 'Only pending revisions can be approved.',
            ], 400);
        }

        $product = $this->approveRevision->execute($revision, $request->user(), $validated['notes'] ?? null);

        return $this->localizedJson([
            'success' => true,
            'data' => new ProductResource($product),
        ]);
    }

    public function reject(Request $request, ProductRevision $revision): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'requested_changes' => ['nullable', 'array'],
            'requested_changes.*.path' => [
                'required_with:requested_changes',
                'string',
                Rule::in(ProductReviewFields::reviewable()),
            ],
            'requested_changes.*.label' => ['nullable', 'string', 'max:150'],
            'requested_changes.*.message' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($revision->status !== ProductRevisionStatus::PENDING) {
            return $this->localizedJson([
                'success' => false,
                'message' => 'Only pending revisions can be rejected.',
            ], 400);
        }

        $revision = $this->rejectRevision->execute(
            $revision,
            $request->user(),
            $validated['reason'],
            $validated['notes'] ?? null,
            $validated['requested_changes'] ?? []
        );

        return $this->localizedJson([
            'success' => true,
            'data' => new ProductRevisionResource($this->products->loadRevision($revision)),
        ]);
    }
}
