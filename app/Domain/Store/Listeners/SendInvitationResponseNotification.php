<?php

namespace App\Domain\Store\Listeners;

use App\Domain\Notification\Services\NotificationService;
use App\Domain\Notification\Support\NotificationMessageResolver;
use App\Domain\Store\Events\InvitationAccepted;
use App\Domain\Store\Events\InvitationDeclined;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendInvitationResponseNotification implements ShouldQueue
{
    public function __construct(private readonly NotificationService $notifications) {}

    public function handle(InvitationAccepted|InvitationDeclined $event): void
    {
        $invitation = $event->invitation;
        $invitation->loadMissing(['store.owner', 'invitee.profile']);

        $store = $invitation->store;
        $owner = $store?->owner;

        if (! $owner) {
            return;
        }

        $isAccepted = $event instanceof InvitationAccepted;
        $type = $isAccepted ? 'employee_invitation_accepted' : 'employee_invitation_rejected';

        $inviteeEmail = $invitation->invitee?->email ?? 'Unknown';
        $inviteeAvatar = $invitation->invitee?->profile?->avatar_url ?? $invitation->invitee?->avatar ?? null;

        $params = [
            'employee_email' => $inviteeEmail,
            'role' => $invitation->role,
        ];

        $resolved = NotificationMessageResolver::resolve($type, $params, $owner);

        $data = [
            'invitation_id' => $invitation->id,
            'store_id' => $store->id,
            'employee_email' => $inviteeEmail,
            'role' => $invitation->role,
        ];

        if ($isAccepted) {
            $data['employee_id'] = $invitation->invitee_user_id;
            $data['employee_avatar_url'] = $inviteeAvatar;
        }

        try {
            $this->notifications->send(
                user: $owner,
                type: $type,
                title: $resolved['title'],
                message: $resolved['message'],
                channel: 'in_app',
                data: $data,
                referenceType: 'store_invitation',
                referenceId: $invitation->id,
                imageUrl: $inviteeAvatar,
            );
        } catch (Throwable $e) {
            Log::error('Invitation response notification failed', [
                'invitation_id' => $invitation->id,
                'store_id' => $store->id,
                'owner_id' => $owner->id,
                'event' => $event::class,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
