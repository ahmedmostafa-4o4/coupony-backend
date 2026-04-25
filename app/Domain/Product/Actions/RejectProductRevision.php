<?php

namespace App\Domain\Product\Actions;

use App\Domain\Product\Enums\ProductApprovalStatus;
use App\Domain\Product\Enums\ProductRevisionStatus;
use App\Domain\Product\Models\Product;
use App\Domain\Product\Models\ProductRevision;
use App\Domain\Product\Repositories\ProductRepository;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\DB;

class RejectProductRevision
{
    public function __construct(private readonly ProductRepository $products) {}

    public function execute(
        ProductRevision $revision,
        User $admin,
        string $reason,
        ?string $notes = null,
        array $requestedChanges = []
    ): ProductRevision {
        return DB::transaction(function () use ($revision, $admin, $reason, $notes, $requestedChanges) {
            $revision = $revision->fresh();

            if ($revision->status !== ProductRevisionStatus::PENDING) {
                throw new \RuntimeException('Only pending revisions can be rejected.');
            }

            /** @var Product $product */
            $product = $revision->product()->firstOrFail();

            if (($product->published_revision_no ?? 0) === 0) {
                $product->update([
                    'approval_status' => ProductApprovalStatus::REJECTED,
                    'rejected_at' => now(),
                    'rejected_by' => $admin->id,
                    'rejection_reason' => $reason,
                    'admin_notes' => $notes,
                ]);
            }

            $revision->update([
                'status' => ProductRevisionStatus::REJECTED,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
                'rejection_reason' => $reason,
                'admin_notes' => $notes,
                'requested_changes' => $requestedChanges,
            ]);

            return $revision->fresh();
        });
    }
}
