<?php

namespace App\Domain\Notification\Services;

use App\Domain\Notification\Models\Notification;
use App\Domain\User\Models\UserDeviceToken;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Contract\Messaging;
use Throwable;

class FcmPushNotificationService
{
    public function __construct(private readonly Messaging $messaging) {}

    public function send(Notification $notification): void
    {
        $notification->loadMissing('user.deviceTokens');

        $tokens = $notification->user?->deviceTokens()
            ->active()
            ->pluck('token')
            ->all() ?? [];

        if ($tokens === []) {
            return;
        }

        try {
            $report = $this->messaging->sendMulticast($this->payload($notification), $tokens);
        } catch (Throwable $e) {
            Log::error('FCM push notification failed', [
                'notification_id' => $notification->id,
                'user_id' => $notification->user_id,
                'token_count' => count($tokens),
                'exception' => $e::class,
                'error' => $e->getMessage(),
            ]);

            return;
        }

        $invalidTokens = array_values(array_unique([
            ...$report->invalidTokens(),
            ...$report->unknownTokens(),
        ]));

        if ($invalidTokens !== []) {
            UserDeviceToken::query()
                ->whereIn('token', $invalidTokens)
                ->update(['revoked_at' => now()]);
        }

        if ($report->hasFailures()) {
            Log::warning('FCM push notification completed with failures', [
                'notification_id' => $notification->id,
                'user_id' => $notification->user_id,
                'token_count' => count($tokens),
                'invalid_token_count' => count($invalidTokens),
            ]);
        }
    }

    public function payload(Notification $notification): array
    {
        return [
            'notification' => [
                'title' => $notification->title,
                'body' => $notification->message,
            ],
            'data' => [
                'notification_id' => (string) $notification->id,
                'type' => (string) $notification->type,
                'reference_type' => (string) ($notification->reference_type ?? ''),
                'reference_id' => (string) ($notification->reference_id ?? ''),
                'channel' => (string) $notification->channel,
                'data' => json_encode($notification->data ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}',
            ],
        ];
    }
}
