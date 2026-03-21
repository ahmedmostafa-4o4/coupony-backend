<?php

namespace App\Domain\Store\Listeners;

use App\Domain\Notification\Enums\NotificationTypes;
use App\Domain\Notification\Services\NotificationService;
use App\Domain\Store\Events\VerificationDocumentRejected;

class NotifyStoreOwnerOnDocumentRejection
{
    public function __construct(
        private NotificationService $notificationService
    ) {
    }

    public function handle(VerificationDocumentRejected $event): void
    {
        $store = $event->verification->store;
        $owner = $store->owner;

        $this->notificationService->send(
            user: $owner,
            type: NotificationTypes::STORE_DOCUMENT_REJECTED,
            title: 'Verification Document Rejected',
            message: "Your {$event->verification->document_type} document for store '{$store->name}' has been rejected. Reason: {$event->reason}",
            data: [
                'store_id' => $store->id,
                'store_name' => $store->name,
                'verification_id' => $event->verification->id,
                'document_type' => $event->verification->document_type,
                'rejected_by' => $event->admin->id,
                'rejection_reason' => $event->reason,
            ],
            channels: ['database', 'email']
        );
    }
}
