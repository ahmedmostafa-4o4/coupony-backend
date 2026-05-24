<?php

namespace App\Domain\Product\Listeners;

use App\Domain\Notification\Models\Notification;
use App\Domain\Notification\Services\NotificationService;
use App\Domain\Notification\Support\NotificationMessageResolver;
use App\Domain\Product\Events\ProductRevisionApproved;
use App\Domain\Product\Models\Product;
use App\Domain\Store\Models\Store;
use App\Domain\User\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendNewOfferNotificationToCustomers implements ShouldQueue
{
    public function __construct(private readonly NotificationService $notifications) {}

    public function handle(ProductRevisionApproved $event): void
    {
        $product = $event->product;
        $product->loadMissing(['store', 'images', 'offer']);

        $store = $product->store;

        if (! $store) {
            return;
        }

        // Only send new_offer if the product has an active offer
        if (! $product->offer) {
            return;
        }

        $storeName = $store->name ?? 'Store';
        $productName = $product->name ?? 'Product';
        $discountValue = $product->offer->discount_value ?? '';
        $expiresAt = $product->offer->expires_at?->toIso8601String();
        $productImage = $product->images?->where('is_primary', true)->first()?->image_url
            ?? $product->images?->first()?->image_url
            ?? $store->logo_url
            ?? null;

        // Get followers of this store
        $followerIds = $store->followerUsers()
            ->wherePivot('notification_enabled', true)
            ->pluck('users.id');

        if ($followerIds->isEmpty()) {
            return;
        }

        // Send to each follower (skip if already notified for this product)
        $followerIds->chunk(100)->each(function ($chunk) use (
            $product, $store, $productName, $storeName, $discountValue, $expiresAt, $productImage
        ) {
            $users = User::whereIn('id', $chunk)->get();

            foreach ($users as $user) {
                // Don't send more than once per product per customer
                $alreadySent = Notification::query()
                    ->where('user_id', $user->id)
                    ->where('type', 'new_offer')
                    ->where('reference_type', Product::class)
                    ->where('reference_id', $product->id)
                    ->exists();

                if ($alreadySent) {
                    continue;
                }

                $resolved = NotificationMessageResolver::resolve('new_offer', [
                    'store_name' => $storeName,
                    'product_name' => $productName,
                    'discount_value' => $discountValue,
                ], $user);

                try {
                    $this->notifications->send(
                        user: $user,
                        type: 'new_offer',
                        title: $resolved['title'],
                        message: $resolved['message'],
                        channel: 'in_app',
                        data: [
                            'product_id' => $product->id,
                            'store_id' => $store->id,
                            'discount_value' => $discountValue,
                            'expires_at' => $expiresAt,
                        ],
                        referenceType: Product::class,
                        referenceId: $product->id,
                        imageUrl: $productImage,
                    );
                } catch (Throwable $e) {
                    Log::error('New offer notification failed', [
                        'user_id' => $user->id,
                        'product_id' => $product->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        });
    }
}
