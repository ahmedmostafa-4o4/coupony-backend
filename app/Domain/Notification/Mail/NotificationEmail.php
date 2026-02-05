<?php

namespace App\Domain\Notification\Mail;

use App\Domain\Notification\Models\Notification;
use App\Domain\User\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NotificationEmail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Notification $notification,
        public User $user
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->notification->title,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mails.notification',
            with: [
                'notification' => $this->notification,
                'user' => $this->user,
                'actionUrl' => $this->getActionUrl(),
                'actionText' => $this->getActionText(),
            ],
        );
    }

    public function getActionUrl(): ?string
    {
        if (!$this->notification->reference_type || !$this->notification->reference_id) {
            return null;
        }

        return match ($this->notification->reference_type) {
            'Order' => route('orders.show', $this->notification->reference_id),
            'Product' => route('products.show', $this->notification->reference_id),
            'Store' => route('stores.show', $this->notification->reference_id),
            default => null,
        };
    }

    public function getActionText(): string
    {
        return match ($this->notification->type) {
            'order_confirmation' => 'View Order',
            'order_shipped' => 'Track Shipment',
            'price_drop' => 'View Product',
            default => 'View Details',
        };
    }
}