<?php

namespace App\Domain\Store\Listeners;

use App\Domain\Notification\Enums\NotificationTypes;
use App\Domain\Notification\Services\NotificationService;
use App\Domain\Store\Events\VerificationDocumentApproved;

class NotifyStoreOwnerOnDocumentApproval
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    public function handle(VerificationDocumentApproved $event): void
    {
        $store = $event->verification->store;
        $owner = $store->owner;

        $this->notificationService->send(
            user: $owner,
            type: NotificationTypes::STORE_DOCUMENT_APPROVED->value,
            title: 'Verification Document Approved',
            message: "Your {$event->verification->document_type} document for store '{$store->name}' has been approved.",
            data: [
                'store_id' => $store->id,
                'store_name' => $store->name,
                'verification_id' => $event->verification->id,
                'document_type' => $event->verification->document_type,
                'approved_by' => $event->admin->id,
                'notes' => $event->notes,
            ],
            channel: 'email'
        );
    }
}
