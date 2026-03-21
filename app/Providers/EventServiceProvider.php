<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        UserRegistered::class => [
            SendWelcomeEmail::class , // Queued: emails queue
            CreateUserPreferences::class , // Queued: default queue
            SendEmailVerification::class , // Queued: emails queue
            AwardRegistrationPoints::class , // Queued: default queue
            TrackUserRegistration::class , // Sync: immediate
            SendAdminNewUserNotification::class , // Queued: default queue
        ],

        \App\Domain\Store\Events\StoreApproved::class => [
            \App\Domain\Store\Listeners\NotifyStoreOwnerOnApproval::class ,
        ],

        \App\Domain\Store\Events\StoreRejected::class => [
            \App\Domain\Store\Listeners\NotifyStoreOwnerOnRejection::class ,
        ],

        \App\Domain\Store\Events\VerificationDocumentApproved::class => [
            \App\Domain\Store\Listeners\NotifyStoreOwnerOnDocumentApproval::class ,
        ],

        \App\Domain\Store\Events\VerificationDocumentRejected::class => [
            \App\Domain\Store\Listeners\NotifyStoreOwnerOnDocumentRejection::class ,
        ],
    ];
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
    //
    }
}
