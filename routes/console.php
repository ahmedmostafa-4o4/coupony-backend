<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Subscription Lifecycle Scheduled Jobs
|--------------------------------------------------------------------------
|
| These jobs manage the subscription lifecycle transitions and notifications.
| They run daily to check for subscriptions that need state changes.
|
*/

Schedule::command('subscription:transition-to-grace')->daily();
Schedule::command('subscription:transition-to-degraded')->daily();
Schedule::command('subscription:transition-to-suspended')->daily();
Schedule::command('subscription:send-expiring-notifications')->daily();

/*
|--------------------------------------------------------------------------
| Notification Scheduled Jobs
|--------------------------------------------------------------------------
|
| Daily analytics summary and milestone checks for store owners.
|
*/

Schedule::command('notifications:daily-analytics-summary')->dailyAt('22:00');
Schedule::command('notifications:check-milestones')->dailyAt('22:30');
