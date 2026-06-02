<?php

namespace App\Domain\Product\Actions;

use App\Domain\Product\Enums\ProductApprovalStatus;
use App\Domain\Product\Enums\ProductRevisionStatus;
use App\Domain\Product\Enums\ProductStatus;
use App\Domain\Product\Events\ProductRevisionApproved;
use App\Domain\Product\Models\Product;
use App\Domain\Product\Models\ProductRevision;
use App\Domain\Product\Repositories\ProductRepository;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\DB;

class ApproveProductRevision
{
    public function __construct(private readonly ProductRepository $products) {}

    public function execute(ProductRevision $revision, User $admin, ?string $notes = null): Product
    {
        return DB::transaction(function () use ($revision, $admin, $notes) {
            $revision = $revision->fresh();

            if ($revision->status !== ProductRevisionStatus::PENDING) {
                throw new \RuntimeException('Only pending revisions can be approved.');
            }

            /** @var Product $product */
            $product = $revision->product()->firstOrFail();
            $product = $this->products->applySnapshot($product, $revision->payload ?? []);

            $product->update([
                'status' => ProductStatus::ACTIVE,
                'approval_status' => ProductApprovalStatus::APPROVED,
                'published_revision_no' => $revision->revision_no,
                'approved_at' => now(),
                'approved_by' => $admin->id,
                'rejected_at' => null,
                'rejected_by' => null,
                'rejection_reason' => null,
                'admin_notes' => $notes,
            ]);

            $revision->update([
                'status' => ProductRevisionStatus::APPROVED,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
                'rejection_reason' => null,
                'admin_notes' => $notes,
            ]);

            // Set offer dates on first approval only (when starts_at is still null)
            $offer = $product->offer;
            if ($offer && $offer->starts_at === null) {
                $startsAt = now();
                $endsAt = $startsAt->copy();

                if ($offer->duration_days) {
                    $endsAt = $endsAt->addDays($offer->duration_days);
                }
                if ($offer->duration_hours) {
                    $endsAt = $endsAt->addHours($offer->duration_hours);
                }

                $offer->update([
                    'starts_at' => $startsAt,
                    'ends_at' => $endsAt,
                ]);
            }

            $fresh = $this->products->loadSellerProduct($product->fresh());

            ProductRevisionApproved::dispatch($fresh, $revision->fresh(), $admin);

            return $fresh;
        });
    }
}
