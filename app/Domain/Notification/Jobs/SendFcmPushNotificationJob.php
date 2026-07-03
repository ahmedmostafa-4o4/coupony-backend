<?php

namespace App\Domain\Notification\Jobs;

use App\Domain\Notification\Models\Notification;
use App\Domain\Notification\Services\FcmPushNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendFcmPushNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly int $notificationId) {}

    public function handle(FcmPushNotificationService $pushNotifications): void
    {
        $notification = Notification::query()->find($this->notificationId);

        if (! $notification) {
            return;
        }

        $pushNotifications->send($notification);
    }
}
