<?php

namespace App\Domain\Product\Listeners;

use App\Domain\Notification\Models\Notification;
use App\Domain\Notification\Services\NotificationService;
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
        $claim->loadMissing('user');
        $store->loadMissing('owner');

        $referenceType = OfferClaim::class;
        $redeemedAt = $claim->redeemed_at?->toIso8601String();

        if ($claim->user) {
            $this->sendOnce($claim->user, [
                'type' => 'offer_redeemed',
                'title' => 'Offer redeemed',
                'message' => 'Your offer was redeemed successfully.',
                'channel' => 'in_app',
                'data' => [
                    'claim_id' => $claim->id,
                    'product_id' => $claim->product_id,
                    'store_id' => $claim->store_id,
                    'redeemed_at' => $redeemedAt,
                ],
                'referenceType' => $referenceType,
                'referenceId' => $claim->id,
            ]);

            if ($event->userPointsAwarded) {
                $this->sendOnce($claim->user, [
                    'type' => 'points_earned',
                    'title' => 'Points earned',
                    'message' => 'You earned points for redeeming an offer.',
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
                ]);
            }
        }

        if (! $store->owner) {
            return;
        }

        $this->sendOnce($store->owner, [
            'type' => 'offer_redeemed_by_employee',
            'title' => 'Offer redeemed',
            'message' => 'An offer claim was redeemed at your store.',
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
        ]);

        if ($event->storePointsAwarded) {
            $this->sendOnce($store->owner, [
                'type' => 'seller_points_earned',
                'title' => 'Store points earned',
                'message' => 'Your store earned points from an offer redemption.',
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
