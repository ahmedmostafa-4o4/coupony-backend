<?php

namespace App\Domain\User\Listeners;

use App\Domain\User\Events\UserRegistered;
use App\Mail\WelcomeEmail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class SendWelcomeEmail implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * The name of the queue connection to use.
     */
    public string $connection = 'redis-default';

    /**
     * The name of the queue to send the job to.
     */
    public string $queue = 'emails';

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying.
     */
    public int $backoff = 60;

    /**
     * Handle the event.
     */
    public function handle(UserRegistered $event): void
    {
        Mail::to($event->user->email)
            ->send(new WelcomeEmail($event->user));
    }

    /**
     * Handle a job failure.
     */
    public function failed(UserRegistered $event, \Throwable $exception): void
    {
        \Log::error('Failed to send welcome email', [
            'user_id' => $event->user->id,
            'email' => $event->user->email,
            'error' => $exception->getMessage(),
        ]);
    }
}
