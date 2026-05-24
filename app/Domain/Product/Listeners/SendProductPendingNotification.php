<?php

namespace App\Domain\Product\Listeners;

use App\Domain\Notification\Services\NotificationService;
use App\Domain\Notification\Support\NotificationMessageResolver;
use App\Domain\Product\Events\ProductRevisionSubmitted;
use App\Domain\Product\Models\Product;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendProductPendingNotification implements ShouldQueue
{
    public function __construct(private readonly NotificationService $notifications) {}

    public function handle(ProductRevisionSubmitted $event): void
    {
        $product = $event->product;
        $product->loadMissing('store.owner', 'images');

        $owner = $product->store?->owner;

        if (! $owner) {
            return;
        }

        $productName = $product->name ?? 'Product';
        $productImage = $product->images?->where('is_primary', true)->first()?->image_url
            ?? $product->images?->first()?->image_url
            ?? null;

        $resolved = NotificationMessageResolver::resolve('product_pending', [
            'product_name' => $productName,
        ], $owner);

        try {
            $this->notifications->send(
                user: $owner,
                type: 'product_pending',
                title: $resolved['title'],
                message: $resolved['message'],
                channel: 'in_app',
                data: [
                    'product_id' => $product->id,
                    'store_id' => $product->store_id,
                    'revision_id' => $event->revision->id,
                ],
                referenceType: Product::class,
                referenceId: $product->id,
                imageUrl: $productImage,
            );
        } catch (Throwable $e) {
            Log::error('Product pending notification failed', [
                'product_id' => $product->id,
                'store_id' => $product->store_id,
                'owner_id' => $owner->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
