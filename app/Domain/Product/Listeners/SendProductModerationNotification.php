<?php

namespace App\Domain\Product\Listeners;

use App\Domain\Notification\Services\NotificationService;
use App\Domain\Notification\Support\NotificationMessageResolver;
use App\Domain\Product\Events\ProductRevisionApproved;
use App\Domain\Product\Events\ProductRevisionRejected;
use App\Domain\Product\Models\Product;
use App\Domain\Product\Models\ProductRevision;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendProductModerationNotification implements ShouldQueue
{
    public function __construct(private readonly NotificationService $notifications) {}

    public function handle(ProductRevisionApproved|ProductRevisionRejected $event): void
    {
        $revision = $event->revision->fresh() ?? $event->revision;
        $product = $event instanceof ProductRevisionApproved
            ? $event->product->fresh()
            : $revision->product;

        if (! $product) {
            return;
        }

        $product->loadMissing('store.owner', 'images');
        $owner = $product->store?->owner;

        if (! $owner) {
            return;
        }

        $isApproved = $event instanceof ProductRevisionApproved;
        $type = $isApproved ? 'product_approved' : 'product_rejected';
        $productName = $product->name ?? 'Product';

        $params = ['product_name' => $productName];
        if (! $isApproved) {
            $params['rejection_reason'] = $event->reason ?? '';
        }

        $resolved = NotificationMessageResolver::resolve($type, $params, $owner);

        $productImage = $product->images?->where('is_primary', true)->first()?->image_url
            ?? $product->images?->first()?->image_url
            ?? null;

        try {
            $this->notifications->send(
                user: $owner,
                type: $type,
                title: $resolved['title'],
                message: $resolved['message'],
                channel: 'in_app',
                data: $isApproved
                    ? $this->approvedData($product, $revision)
                    : $this->rejectedData($product, $revision, $event->reason),
                referenceType: Product::class,
                referenceId: $product->id,
                imageUrl: $productImage,
            );
        } catch (Throwable $e) {
            Log::error('Product moderation notification failed', [
                'product_id' => $product->id,
                'store_id' => $product->store_id,
                'revision_id' => $revision->id,
                'owner_id' => $product->store?->owner_user_id,
                'event' => $event::class,
                'error' => $e->getMessage(),
                'exception' => $e::class,
            ]);
        }
    }

    private function approvedData(Product $product, ProductRevision $revision): array
    {
        return [
            'product_id' => $product->id,
            'store_id' => $product->store_id,
            'revision_id' => $revision->id,
        ];
    }

    private function rejectedData(Product $product, ProductRevision $revision, string $reason): array
    {
        return [
            'product_id' => $product->id,
            'store_id' => $product->store_id,
            'revision_id' => $revision->id,
            'rejection_reason' => $reason,
        ];
    }
}
