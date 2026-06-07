<?php

namespace App\Domain\Notification\Events;

use App\Domain\Notification\Models\Notification;
use App\Domain\User\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NotificationSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Notification $notification,
        public User $user
    ) {}

    public function broadcastOn(): array
    {
        $channelName = method_exists($this->user, 'receivesBroadcastNotificationsOn') 
            ? $this->user->receivesBroadcastNotificationsOn() 
            : 'users.'.$this->user->id;

        return [
            new PrivateChannel($channelName),
        ];
    }

    public function broadcastAs(): string
    {
        return 'notification.sent';
    }

    public function broadcastWith(): array
    {
        return [
            'notification' => [
                'id' => $this->notification->id,
                'type' => $this->notification->type,
                'title' => $this->notification->title,
                'message' => $this->notification->message,
                'data' => $this->notification->data,
                'image_url' => $this->notification->image_url,
                'badge_status' => $this->notification->badge_status ?? 'none',
                'channel' => $this->notification->channel,
                'status' => $this->notification->status,
                'reference_type' => $this->notification->reference_type,
                'reference_id' => $this->notification->reference_id,
                'read_at' => $this->notification->read_at?->toIso8601String(),
                'created_at' => $this->notification->created_at->toIso8601String(),
            ],
            'unread_count' => $this->user->unreadNotifications()->count(),
        ];
    }

    public function broadcastWhen(): bool
    {
        return (bool) config('broadcasting.enabled', true);
    }
}
