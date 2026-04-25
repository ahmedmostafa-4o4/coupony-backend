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
use App\Domain\Product\Support\ProductRequestedChangeFields;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

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

        $validated = $this->validateRejectPayload($request);

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

    private function validateRejectPayload(Request $request): array
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'requested_changes' => ['nullable', 'array'],
            'requested_changes.*.section' => [
                'required_with:requested_changes',
                'string',
                Rule::in(ProductRequestedChangeFields::sections()),
            ],
            'requested_changes.*.field' => ['nullable', 'string', 'max:100'],
            'requested_changes.*.path' => ['nullable', 'string', 'max:500'],
            'requested_changes.*.selector' => ['nullable', 'array'],
            'requested_changes.*.selector.id' => ['nullable', 'integer'],
            'requested_changes.*.selector.sku' => ['nullable', 'string', 'max:100'],
            'requested_changes.*.selector.uid' => ['nullable', 'string', 'max:100'],
            'requested_changes.*.selector.image_url' => ['nullable', 'string', 'max:500'],
            'requested_changes.*.selector.index' => ['nullable', 'integer', 'min:0'],
            'requested_changes.*.variant_selector' => ['nullable', 'array'],
            'requested_changes.*.variant_selector.id' => ['nullable', 'integer'],
            'requested_changes.*.variant_selector.sku' => ['nullable', 'string', 'max:100'],
            'requested_changes.*.variant_selector.uid' => ['nullable', 'string', 'max:100'],
            'requested_changes.*.variant_selector.index' => ['nullable', 'integer', 'min:0'],
            'requested_changes.*.attribute_selector' => ['nullable', 'array'],
            'requested_changes.*.attribute_selector.name' => ['nullable', 'string', 'max:100'],
            'requested_changes.*.attribute_selector.uid' => ['nullable', 'string', 'max:100'],
            'requested_changes.*.attribute_selector.index' => ['nullable', 'integer', 'min:0'],
            'requested_changes.*.label' => ['nullable', 'string', 'max:150'],
            'requested_changes.*.message' => ['nullable', 'string', 'max:1000'],
        ]);

        $errors = [];

        foreach ($validated['requested_changes'] ?? [] as $index => $change) {
            $section = $change['section'] ?? null;
            $field = $change['field'] ?? 'value';

            if (! is_string($section) || ! ProductRequestedChangeFields::isAllowed($section, $field)) {
                $errors["requested_changes.$index.field"] = ["The selected field is not allowed for section {$section}."];
            }

            if ($section === 'variants' && ! $this->hasAnySelector($change['selector'] ?? [], ['uid', 'sku', 'id', 'index'])) {
                $errors["requested_changes.$index.selector"] = ['A variant selector is required.'];
            }

            if ($section === 'images' && ! $this->hasAnySelector($change['selector'] ?? [], ['uid', 'image_url', 'id', 'index'])) {
                $errors["requested_changes.$index.selector"] = ['An image selector is required.'];
            }

            if ($section === 'variant_attributes') {
                if (! $this->hasAnySelector($change['variant_selector'] ?? [], ['uid', 'sku', 'id', 'index'])) {
                    $errors["requested_changes.$index.variant_selector"] = ['A variant selector is required.'];
                }

                if (! $this->hasAnySelector($change['attribute_selector'] ?? [], ['uid', 'name', 'index'])) {
                    $errors["requested_changes.$index.attribute_selector"] = ['An attribute selector is required.'];
                }
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return $validated;
    }

    private function hasAnySelector(array $selector, array $keys): bool
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $selector) && $selector[$key] !== null && $selector[$key] !== '') {
                return true;
            }
        }

        return false;
    }
}
