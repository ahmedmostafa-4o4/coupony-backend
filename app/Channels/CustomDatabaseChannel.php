<?php

namespace App\Channels;

use Illuminate\Notifications\Notification;
use App\Domain\Notification\Models\Notification as CustomNotification;

class CustomDatabaseChannel
{
    public function send($notifiable, Notification $notification)
    {
        if (method_exists($notification, 'toDatabase')) {
            $data = $notification->toDatabase($notifiable);

            return CustomNotification::create([
                'user_id' => $notifiable->id,
                'type' => class_basename($notification),
                'title' => $data['title'] ?? 'Notification',
                'message' => $data['message'] ?? '',
                'data' => $data['data'] ?? [],
                'status' => 'pending',
                'reference_type' => $data['reference_type'] ?? null,
                'reference_id' => $data['reference_id'] ?? null,
                'channel' => 'system',
            ]);
        }
    }
}
