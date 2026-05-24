<?php

namespace App\Domain\Product\Listeners;

use App\Domain\Notification\Services\NotificationService;
use App\Domain\Notification\Support\NotificationMessageResolver;
use App\Domain\Product\Events\OfferClaimCreated;
use App\Domain\Product\Models\OfferClaim;
use App\Domain\User\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendOfferClaimCreatedNotifications implements ShouldQueue
{
    public function __construct(private readonly NotificationService $notifications) {}

    public function handle(OfferClaimCreated $event): void
    {
        $claim = $event->claim;
        $product = $event->product;
        $customer = $event->customer;
        $referenceType = OfferClaim::class;

        $product->loadMissing('store.owner', 'images');
        $store = $product->store;
        $storeName = $store?->name ?? 'Store';
        $productName = $product->name ?? 'Product';
        $storeLogo = $store?->logo_url ?? null;
        $customerAvatar = $customer->avatar ?? $customer->profile?->avatar_url ?? null;

        // Notify customer
        $customerResolved = NotificationMessageResolver::resolve('offer_claim_created', [
            'product_name' => $productName,
            'expires_at' => $claim->expires_at?->toIso8601String() ?? '',
        ], $customer);

        $this->sendSafely($customer, [
            'type' => 'offer_claim_created',
            'title' => $customerResolved['title'],
            'message' => $customerResolved['message'],
            'channel' => 'in_app',
            'data' => [
                'claim_id' => $claim->id,
                'product_id' => $product->id,
                'store_id' => $product->store_id,
                'expires_at' => $claim->expires_at?->toIso8601String(),
            ],
            'referenceType' => $referenceType,
            'referenceId' => $claim->id,
            'imageUrl' => $storeLogo,
        ]);

        if (! $store) {
            return;
        }

        // Notify store owner and employees
        $store->loadMissing('owner', 'employees');

        $storeNotificationParams = [
            'customer_email' => $customer->email ?? '',
            'product_name' => $productName,
        ];

        if ($store->owner) {
            $ownerResolved = NotificationMessageResolver::resolve('new_offer_claim', $storeNotificationParams, $store->owner);

            $storeNotification = [
                'type' => 'new_offer_claim',
                'title' => $ownerResolved['title'],
                'message' => $ownerResolved['message'],
                'channel' => 'in_app',
                'data' => [
                    'claim_id' => $claim->id,
                    'product_id' => $product->id,
                    'store_id' => $product->store_id,
                    'customer_id' => $customer->id,
                ],
                'referenceType' => $referenceType,
                'referenceId' => $claim->id,
                'imageUrl' => $customerAvatar,
            ];

            $this->sendSafely($store->owner, $storeNotification);
        }

        $store->employees
            ->reject(fn (User $employee) => (string) $employee->id === (string) $store->owner_user_id)
            ->each(function (User $employee) use ($storeNotificationParams, $claim, $product, $customer, $referenceType, $customerAvatar) {
                $resolved = NotificationMessageResolver::resolve('new_offer_claim', $storeNotificationParams, $employee);

                $this->sendSafely($employee, [
                    'type' => 'new_offer_claim',
                    'title' => $resolved['title'],
                    'message' => $resolved['message'],
                    'channel' => 'in_app',
                    'data' => [
                        'claim_id' => $claim->id,
                        'product_id' => $product->id,
                        'store_id' => $product->store_id,
                        'customer_id' => $customer->id,
                    ],
                    'referenceType' => $referenceType,
                    'referenceId' => $claim->id,
                    'imageUrl' => $customerAvatar,
                ]);
            });
    }

    private function sendSafely(User $user, array $notification): void
    {
        try {
            $this->notifications->send(
                user: $user,
                type: $notification['type'],
                title: $notification['title'],
                message: $notification['message'],
                channel: $notification['channel'],
                data: $notification['data'],
                referenceType: $notification['referenceType'],
                referenceId: $notification['referenceId'],
                imageUrl: $notification['imageUrl'] ?? null,
            );
        } catch (Throwable $e) {
            Log::error('Offer claim notification failed', [
                'user_id' => $user->id,
                'type' => $notification['type'] ?? null,
                'reference_type' => $notification['referenceType'] ?? null,
                'reference_id' => $notification['referenceId'] ?? null,
                'error' => $e->getMessage(),
                'exception' => $e::class,
            ]);
        }
    }
}
