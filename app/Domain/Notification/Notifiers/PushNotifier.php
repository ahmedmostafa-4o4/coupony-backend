<?php

namespace App\Domain\Notification\Notifiers;

use App\Domain\Notification\Contracts\NotifierInterface;
use App\Domain\Notification\Models\Notification;
use App\Domain\User\Models\User;
use Http;

class PushNotifier implements NotifierInterface
{
    public function send(Notification $notification, User $user): void
    {
        // Using Firebase Cloud Messaging (example)
        $this->sendViaFCM($notification, $user);
    }

    private function sendViaFCM(Notification $notification, User $user): void
    {
        $serverKey = config('services.fcm.server_key');

        // Get user's device tokens (you need a device_tokens table)
        $deviceTokens = $this->getUserDeviceTokens($user);

        if (empty($deviceTokens)) {
            throw new \Exception('User has no registered devices');
        }

        $payload = [
            'registration_ids' => $deviceTokens,
            'notification' => [
                'title' => $notification->title,
                'body' => $notification->message,
                'sound' => 'default',
                'badge' => $this->getUnreadCount($user),
            ],
            'data' => [
                'notification_id' => $notification->id,
                'type' => $notification->type,
                'reference_type' => $notification->reference_type,
                'reference_id' => $notification->reference_id,
            ],
        ];

        Http::withHeaders([
            'Authorization' => "key={$serverKey}",
            'Content-Type' => 'application/json',
        ])
            ->post('https://fcm.googleapis.com/fcm/send', $payload)
            ->throw();
    }

    private function getUserDeviceTokens(User $user): array
    {
        // Implement device token storage and retrieval
        // This is a placeholder
        return [];
    }

    private function getUnreadCount(User $user): int
    {
        return $user->unreadNotifications()->count();
    }
}
