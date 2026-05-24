<?php

namespace App\Domain\Subscription\Support;

use App\Domain\Subscription\Jobs\SendExpiringNotificationJob;
use App\Domain\Subscription\Jobs\TransitionToDegradedJob;
use App\Domain\Subscription\Jobs\TransitionToGraceJob;
use App\Domain\Subscription\Jobs\TransitionToSuspendedJob;
use Illuminate\Support\ServiceProvider;

class SubscriptionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                TransitionToGraceJob::class,
                TransitionToDegradedJob::class,
                TransitionToSuspendedJob::class,
                SendExpiringNotificationJob::class,
            ]);
        }
    }
}
