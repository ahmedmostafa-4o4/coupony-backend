<?php

namespace App\Providers;

use App\Domain\Product\Events\OfferClaimCreated;
use App\Domain\Product\Events\OfferClaimRedeemed;
use App\Domain\Product\Events\ProductRevisionApproved;
use App\Domain\Product\Events\ProductRevisionRejected;
use App\Domain\Product\Listeners\SendOfferClaimCreatedNotifications;
use App\Domain\Product\Listeners\SendOfferRedeemedNotifications;
use App\Domain\Product\Listeners\SendProductModerationNotification;
use App\Domain\Store\Events\StoreApproved;
use App\Domain\Store\Events\StoreRejected;
use App\Domain\Store\Events\VerificationDocumentApproved;
use App\Domain\Store\Events\VerificationDocumentRejected;
use App\Domain\Store\Listeners\NotifyStoreOwnerOnDocumentApproval;
use App\Domain\Store\Listeners\NotifyStoreOwnerOnDocumentRejection;
use App\Domain\Store\Listeners\SendStoreModerationNotification;
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
        Event::listen(StoreApproved::class, SendStoreModerationNotification::class);
        Event::listen(StoreRejected::class, SendStoreModerationNotification::class);
        Event::listen(VerificationDocumentApproved::class, NotifyStoreOwnerOnDocumentApproval::class);
        Event::listen(VerificationDocumentRejected::class, NotifyStoreOwnerOnDocumentRejection::class);

        Event::listen(OfferClaimCreated::class, SendOfferClaimCreatedNotifications::class);
        Event::listen(OfferClaimRedeemed::class, SendOfferRedeemedNotifications::class);
        Event::listen(ProductRevisionApproved::class, SendProductModerationNotification::class);
        Event::listen(ProductRevisionRejected::class, SendProductModerationNotification::class);
    }
}
