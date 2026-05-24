<?php

namespace App\Domain\Product\Listeners;

use App\Domain\Notification\Models\Notification;
use App\Domain\Notification\Services\NotificationService;
use App\Domain\Notification\Support\NotificationMessageResolver;
use App\Domain\Product\Events\OfferClaimRedeemed;
use App\Domain\Product\Models\OfferClaim;
use App\Domain\User\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendOfferRedeemedNotifications implements ShouldQueue
{
    public function __construct(private readonly NotificationService $notifications) {}

    public function handle(OfferClaimRedeemed $event): void
    {
        $claim = $event->claim;
        $store = $event->store;
        $claim->loadMissing('user.profile', 'product');
        $store->loadMissing('owner');

        $referenceType = OfferClaim::class;
        $redeemedAt = $claim->redeemed_at?->toIso8601String();
        $productName = $claim->product?->name ?? 'Product';
        $storeName = $store->name ?? 'Store';
        $storeLogo = $store->logo_url ?? null;
        $customerAvatar = $claim->user?->avatar ?? $claim->user?->profile?->avatar_url ?? null;

        // Notify customer: offer_redeemed
        if ($claim->user) {
            $customerResolved = NotificationMessageResolver::resolve('offer_redeemed', [
                'product_name' => $productName,
                'store_name' => $storeName,
            ], $claim->user);

            $this->sendOnce($claim->user, [
                'type' => 'offer_redeemed',
                'title' => $customerResolved['title'],
                'message' => $customerResolved['message'],
                'channel' => 'in_app',
                'data' => [
                    'claim_id' => $claim->id,
                    'product_id' => $claim->product_id,
                    'store_id' => $claim->store_id,
                    'redeemed_at' => $redeemedAt,
                ],
                'referenceType' => $referenceType,
                'referenceId' => $claim->id,
                'imageUrl' => $storeLogo,
            ]);

            // Notify customer: points_earned
            if ($event->userPointsAwarded) {
                $pointsResolved = NotificationMessageResolver::resolve('points_earned', [
                    'points' => $event->userPoints,
                    'product_name' => $productName,
                ], $claim->user);

                $this->sendOnce($claim->user, [
                    'type' => 'points_earned',
                    'title' => $pointsResolved['title'],
                    'message' => $pointsResolved['message'],
                    'channel' => 'in_app',
                    'data' => [
                        'points' => $event->userPoints,
                        'reason' => 'offer_redeemed',
                        'claim_id' => $claim->id,
                        'product_id' => $claim->product_id,
                        'store_id' => $claim->store_id,
                    ],
                    'referenceType' => $referenceType,
                    'referenceId' => $claim->id,
                    'imageUrl' => null,
                ]);
            }
        }

        if (! $store->owner) {
            return;
        }

        // Notify store owner: offer_redeemed_by_employee
        $employeeEmail = $event->redeemedBy->email ?? '';
        $customerEmail = $claim->user?->email ?? '';

        $ownerResolved = NotificationMessageResolver::resolve('offer_redeemed_by_employee', [
            'employee_email' => $employeeEmail,
            'product_name' => $productName,
            'customer_email' => $customerEmail,
        ], $store->owner);

        $this->sendOnce($store->owner, [
            'type' => 'offer_redeemed_by_employee',
            'title' => $ownerResolved['title'],
            'message' => $ownerResolved['message'],
            'channel' => 'in_app',
            'data' => [
                'claim_id' => $claim->id,
                'product_id' => $claim->product_id,
                'store_id' => $claim->store_id,
                'customer_id' => $claim->user_id,
                'redeemed_by' => $event->redeemedBy->id,
            ],
            'referenceType' => $referenceType,
            'referenceId' => $claim->id,
            'imageUrl' => $customerAvatar,
        ]);

        // Notify store owner: seller_points_earned
        if ($event->storePointsAwarded) {
            $storePointsResolved = NotificationMessageResolver::resolve('seller_points_earned', [
                'points' => $event->storePoints,
                'product_name' => $productName,
            ], $store->owner);

            $this->sendOnce($store->owner, [
                'type' => 'seller_points_earned',
                'title' => $storePointsResolved['title'],
                'message' => $storePointsResolved['message'],
                'channel' => 'in_app',
                'data' => [
                    'points' => $event->storePoints,
                    'reason' => 'offer_redeemed',
                    'claim_id' => $claim->id,
                    'product_id' => $claim->product_id,
                    'store_id' => $claim->store_id,
                ],
                'referenceType' => $referenceType,
                'referenceId' => $claim->id,
                'imageUrl' => null,
            ]);
        }
    }

    private function sendOnce(User $user, array $notification): void
    {
        if (Notification::query()
            ->where('user_id', $user->id)
            ->where('type', $notification['type'])
            ->where('reference_type', $notification['referenceType'])
            ->where('reference_id', $notification['referenceId'])
            ->exists()) {
            return;
        }

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
            Log::error('Offer redemption notification failed', [
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
