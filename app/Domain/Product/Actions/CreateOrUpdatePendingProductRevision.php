<?php

namespace App\Domain\Product\Actions;

use App\Domain\Product\DTOs\ProductData;
use App\Domain\Product\Enums\ProductApprovalStatus;
use App\Domain\Product\Enums\ProductRevisionAction;
use App\Domain\Product\Enums\ProductRevisionStatus;
use App\Domain\Product\Events\ProductRevisionSubmitted;
use App\Domain\Product\Models\Product;
use App\Domain\Product\Models\ProductRevision;
use App\Domain\Product\Repositories\ProductRepository;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\DB;
use Log;

class CreateOrUpdatePendingProductRevision
{
    public function __construct(
        private readonly ProductRepository $products,
        private readonly ResolveVariantOfferPricing $pricing,
    ) {}

    public function execute(
        Product $product,
        ProductData $data,
        User $submittedBy,
        array $reviewFields = []
    ): ProductRevision {
        try {
            return DB::transaction(function () use ($product, $data, $submittedBy, $reviewFields) {
                $product = $product->fresh();
                $pendingRevision = $this->products->pendingRevision($product);
                $payload = $product->approval_status === ProductApprovalStatus::APPROVED
                    ? $this->mergePayload($product, $data, $pendingRevision?->payload ?? $this->products->snapshotPayload($product))
                    : $this->products->snapshotPayload($product);

                if ($pendingRevision) {
                    $pendingRevision->update([
                        'action' => ProductRevisionAction::RESUBMIT,
                        'base_revision_no' => $product->published_revision_no ?: null,
                        'submitted_by' => $submittedBy->id,
                        'submitted_at' => now(),
                        'reviewed_by' => null,
                        'reviewed_at' => null,
                        'rejection_reason' => null,
                        'admin_notes' => null,
                        'payload' => $payload,
                        'review_fields' => array_values(array_unique([
                            ...($pendingRevision->review_fields ?? []),
                            ...$reviewFields,
                        ])),
                        'requested_changes' => null,
                    ]);

                    event(new ProductRevisionSubmitted($product, $pendingRevision->fresh(), $submittedBy));

                    return $pendingRevision->fresh();
                }

                $revision = $product->revisions()->create([
                    'revision_no' => $this->products->nextRevisionNumber($product),
                    'action' => $product->published_revision_no > 0
                        ? ProductRevisionAction::UPDATE
                        : ProductRevisionAction::CREATE,
                    'status' => ProductRevisionStatus::PENDING,
                    'base_revision_no' => $product->published_revision_no ?: null,
                    'submitted_by' => $submittedBy->id,
                    'submitted_at' => now(),
                    'payload' => $payload,
                    'review_fields' => $reviewFields,
                    'requested_changes' => null,
                ]);

                event(new ProductRevisionSubmitted($product, $revision, $submittedBy));

                return $revision;
            });
        } catch (\Exception $e) {
            Log::error("Failed to create or update pending product revision for product ID {$product->id}: ".$e->getMessage(), [
                'product_id' => $product->id,
                'user_id' => $submittedBy->id,
                'exception' => $e,
            ]);
            throw $e;
        }
    }

    private function mergePayload(Product $product, ProductData $data, array $payload): array
    {
        $payload['product'] ??= [];
        $payload['images'] ??= [];
        $payload['variants'] ??= [];
        $payload['offer'] ??= null;

        foreach ($data->attributes() as $key => $value) {
            $payload['product'][$key] = $value;
        }

        if ($data->hasCategoryIds()) {
            $payload['product']['category_ids'] = $data->categoryIds();
        }

        if ($data->hasImages()) {
            $imageResult = $this->products->storePendingImages($product, $data->images());
            $payload['images'] = $imageResult['images'];
        }

        if ($data->hasVariants()) {
            $payload['variants'] = $data->variants();
        }

        if ($data->hasOffer()) {
            $payload['offer'] = $data->offer();
        }

        if ($payload['offer'] !== null && $payload['variants'] !== []) {
            $payload['variants'] = $this->pricing->resolve($payload['variants'], $payload['offer']);
            $payload['product'] = [
                ...$payload['product'],
                ...$this->pricing->deriveProductPricingSummary($payload['variants']),
            ];
        }

        return $payload;
    }
}
