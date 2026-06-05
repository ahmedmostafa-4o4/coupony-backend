<?php

namespace App\Notifications\Admin;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use App\Channels\CustomDatabaseChannel;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\BroadcastMessage;

abstract class AdminNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function via($notifiable): array
    {
        return [CustomDatabaseChannel::class, 'broadcast', 'mail'];
    }

    abstract public function toDatabase($notifiable): array;

    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'notification' => $this->toDatabase($notifiable)
        ]);
    }

    // By default, toMail can be overridden by subclasses
    public function toMail($notifiable): MailMessage
    {
        $data = $this->toDatabase($notifiable);
        return (new MailMessage)
            ->subject('Admin Alert: ' . $data['title'])
            ->line($data['message']);
    }
}
