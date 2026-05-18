<?php

namespace App\Domain\Product\Listeners;

use App\Domain\Notification\Services\NotificationService;
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

        $this->sendSafely($customer, [
            'type' => 'offer_claim_created',
            'title' => 'Offer claimed successfully',
            'message' => 'Your offer claim has been created successfully.',
            'channel' => 'in_app',
            'data' => [
                'claim_id' => $claim->id,
                'product_id' => $product->id,
                'store_id' => $product->store_id,
                'expires_at' => $claim->expires_at?->toIso8601String(),
            ],
            'referenceType' => $referenceType,
            'referenceId' => $claim->id,
        ]);

        $store = $product->store;

        if (! $store) {
            return;
        }

        $storeNotification = [
            'type' => 'new_offer_claim',
            'title' => 'New offer claim',
            'message' => 'A customer claimed an offer from your store.',
            'channel' => 'in_app',
            'data' => [
                'claim_id' => $claim->id,
                'product_id' => $product->id,
                'store_id' => $product->store_id,
                'customer_id' => $customer->id,
            ],
            'referenceType' => $referenceType,
            'referenceId' => $claim->id,
        ];

        $store->loadMissing('owner', 'employees');

        if ($store->owner) {
            $this->sendSafely($store->owner, $storeNotification);
        }

        $store->employees
            ->reject(fn (User $employee) => (string) $employee->id === (string) $store->owner_user_id)
            ->each(fn (User $employee) => $this->sendSafely($employee, $storeNotification));
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
