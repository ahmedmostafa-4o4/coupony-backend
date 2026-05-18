<?php

namespace App\Domain\User\Events;

use App\Domain\User\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserLoggedOut
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public User $user;

    /**
     * Create a new event instance.
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel(`user.{$this->user->id}`),
        ];
    }

    public function broadcastAs(): string
    {
        return 'user.logged_out';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'user_id' => $this->user->id,
            'email' => $this->user->email,
            'registered_at' => $this->user->created_at->toIso8601String(),
        ];
    }

    public function broadcastWhen(): bool
    {
        return config('broadcasting.enabled', true);
    }
}
