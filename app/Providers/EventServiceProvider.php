<?php

namespace App\Providers;

use App\Domain\Product\Events\OfferClaimCreated;
use App\Domain\Product\Events\OfferClaimRedeemed;
use App\Domain\Product\Events\ProductRevisionApproved;
use App\Domain\Product\Events\ProductRevisionRejected;
use App\Domain\Product\Events\ProductRevisionSubmitted;
use App\Domain\Product\Listeners\SendNewOfferNotificationToCustomers;
use App\Domain\Product\Listeners\SendOfferClaimCreatedNotifications;
use App\Domain\Product\Listeners\SendOfferRedeemedNotifications;
use App\Domain\Product\Listeners\SendProductModerationNotification;
use App\Domain\Product\Listeners\SendProductPendingNotification;
use App\Domain\Store\Events\InvitationAccepted;
use App\Domain\Store\Events\InvitationDeclined;
use App\Domain\Store\Events\StoreApproved;
use App\Domain\Store\Events\StoreCreated;
use App\Domain\Store\Events\StoreFollowed;
use App\Domain\Store\Events\StoreRejected;
use App\Domain\Store\Events\VerificationDocumentApproved;
use App\Domain\Store\Events\VerificationDocumentRejected;
use App\Domain\Store\Listeners\NotifyStoreOwnerOnDocumentApproval;
use App\Domain\Store\Listeners\NotifyStoreOwnerOnDocumentRejection;
use App\Domain\Store\Listeners\SendInvitationResponseNotification;
use App\Domain\Store\Listeners\SendNewFollowerNotification;
use App\Domain\Store\Listeners\SendStoreModerationNotification;
use App\Domain\Store\Listeners\SendStorePendingNotification;
use App\Domain\Subscription\Events\SubscriptionPaymentApproved;
use App\Domain\Subscription\Events\SubscriptionPaymentFailed;
use App\Domain\Subscription\Events\SubscriptionStatusChanged;
use App\Domain\Subscription\Listeners\SendSubscriptionNotification;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Store moderation
        Event::listen(StoreApproved::class, SendStoreModerationNotification::class);
        Event::listen(StoreRejected::class, SendStoreModerationNotification::class);
        Event::listen(VerificationDocumentApproved::class, NotifyStoreOwnerOnDocumentApproval::class);
        Event::listen(VerificationDocumentRejected::class, NotifyStoreOwnerOnDocumentRejection::class);

        // Store pending (new store submitted)
        Event::listen(StoreCreated::class, SendStorePendingNotification::class);

        // Store followers
        Event::listen(StoreFollowed::class, SendNewFollowerNotification::class);

        // Employee invitations
        Event::listen(InvitationAccepted::class, SendInvitationResponseNotification::class);
        Event::listen(InvitationDeclined::class, SendInvitationResponseNotification::class);

        // Offer claims & redemptions
        Event::listen(OfferClaimCreated::class, SendOfferClaimCreatedNotifications::class);
        Event::listen(OfferClaimRedeemed::class, SendOfferRedeemedNotifications::class);

        // Product moderation
        Event::listen(ProductRevisionApproved::class, SendProductModerationNotification::class);
        Event::listen(ProductRevisionRejected::class, SendProductModerationNotification::class);

        // Product pending (revision submitted)
        Event::listen(ProductRevisionSubmitted::class, SendProductPendingNotification::class);

        // New offer notification to customers (when product is approved)
        Event::listen(ProductRevisionApproved::class, SendNewOfferNotificationToCustomers::class);

        // Subscriptions
        Event::listen(SubscriptionPaymentApproved::class, SendSubscriptionNotification::class);
        Event::listen(SubscriptionPaymentFailed::class, SendSubscriptionNotification::class);
        Event::listen(SubscriptionStatusChanged::class, SendSubscriptionNotification::class);
    }
}
