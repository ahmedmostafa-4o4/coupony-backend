<?php

namespace App\Domain\Store\Services;

use App\Domain\Notification\Services\NotificationService;
use App\Domain\Store\Enums\InvitationStatus;
use App\Domain\Store\Events\InvitationAccepted;
use App\Domain\Store\Events\InvitationCancelled;
use App\Domain\Store\Events\InvitationDeclined;
use App\Domain\Store\Events\InvitationSent;
use App\Domain\Store\Models\Store;
use App\Domain\Store\Models\StoreEmployee;
use App\Domain\Store\Models\StoreInvitation;
use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StoreInvitationService
{
    public function __construct(private NotificationService $notificationService)
    {
    }

    public function sendInvitation(Store $store, User $sender, array $data): StoreInvitation
    {
        $invitee = User::where('email', $data['email'])->first();

        if (!$invitee) {
            throw ValidationException::withMessages([
                'email' => [__('api.invitation.user_not_found')],
            ]);
        }

        if ($sender->id === $invitee->id) {
            throw ValidationException::withMessages([
                'email' => [__('api.invitation.self_invite')],
            ]);
        }

        if ($store->hasEmployee($invitee)) {
            throw ValidationException::withMessages([
                'email' => [__('api.invitation.already_employee')],
            ]);
        }

        $existingPending = $store->invitations()
            ->pending()
            ->where('invitee_user_id', $invitee->id)
            ->where('expires_at', '>', now())
            ->exists();

        if ($existingPending) {
            throw ValidationException::withMessages([
                'email' => [__('api.invitation.already_pending')],
            ]);
        }

        if ($store->hasReachedEmployeeLimit()) {
            throw ValidationException::withMessages([
                'email' => [__('api.invitation.limit_reached')],
            ]);
        }

        if (!empty($data['address_id'])) {
            $addressExists = $store->addresses()->where('addresses.id', $data['address_id'])->exists();
            if (!$addressExists) {
                throw ValidationException::withMessages([
                    'address_id' => [__('api.common.validation_failed')],
                ]);
            }
        }

        return DB::transaction(function () use ($store, $sender, $invitee, $data) {
            $expiryHours = config('store.invitation_expiry_hours', 48);
            
            $invitation = $store->invitations()->create([
                'invited_by_user_id' => $sender->id,
                'invitee_user_id' => $invitee->id,
                'address_id' => $data['address_id'] ?? null,
                'role' => $data['role'],
                'permissions' => $data['permissions'] ?? null,
                'status' => InvitationStatus::PENDING,
                'message' => $data['message'] ?? null,
                'expires_at' => now()->addHours($expiryHours),
            ]);

            $this->notificationService->send(
                $invitee,
                'store_invitation',
                __('api.invitation.notification_title', ['store' => $store->name]),
                __('api.invitation.notification_message', ['store' => $store->name]),
                'email',
                [
                    'invitation_id' => $invitation->id,
                    'store_id' => $store->id,
                ]
            );

            event(new InvitationSent($invitation));

            return $invitation;
        });
    }

    public function acceptInvitation(User $invitee, StoreInvitation $invitation): void
    {
        if ($invitation->invitee_user_id !== $invitee->id) {
            throw ValidationException::withMessages([
                'invitation' => [__('api.common.unauthorized')],
            ]);
        }

        if (!$invitation->isPending()) {
            throw ValidationException::withMessages([
                'invitation' => [__('api.invitation.not_pending')],
            ]);
        }

        if ($invitation->isExpired()) {
            $invitation->markAsExpired();
            throw ValidationException::withMessages([
                'invitation' => [__('api.invitation.expired')],
            ]);
        }

        DB::transaction(function () use ($invitee, $invitation) {
            StoreEmployee::updateOrCreate(
                [
                    'store_id' => $invitation->store_id,
                    'user_id' => $invitee->id,
                ],
                [
                    'role' => $invitation->role,
                    'permissions' => $invitation->permissions,
                    'address_id' => $invitation->address_id,
                ]
            );

            $invitation->markAsAccepted();

            event(new InvitationAccepted($invitation));
        });
    }

    public function declineInvitation(User $invitee, StoreInvitation $invitation): void
    {
        if ($invitation->invitee_user_id !== $invitee->id) {
            throw ValidationException::withMessages([
                'invitation' => [__('api.common.unauthorized')],
            ]);
        }

        if (!$invitation->isPending() || $invitation->isExpired()) {
            throw ValidationException::withMessages([
                'invitation' => [__('api.invitation.cannot_decline')],
            ]);
        }

        $invitation->markAsDeclined();

        event(new InvitationDeclined($invitation));
    }

    public function cancelInvitation(StoreInvitation $invitation): void
    {
        if (!$invitation->isPending() || $invitation->isExpired()) {
            throw ValidationException::withMessages([
                'invitation' => [__('api.invitation.cannot_cancel')],
            ]);
        }

        $invitation->markAsCancelled();

        event(new InvitationCancelled($invitation));
    }

    public function resendInvitation(StoreInvitation $invitation): StoreInvitation
    {
        if (!$invitation->isPending() && !$invitation->isExpired()) {
            throw ValidationException::withMessages([
                'invitation' => [__('api.invitation.cannot_resend')],
            ]);
        }

        return DB::transaction(function () use ($invitation) {
            $expiryHours = config('store.invitation_expiry_hours', 48);
            
            $invitation->update([
                'status' => InvitationStatus::PENDING,
                'expires_at' => now()->addHours($expiryHours),
            ]);

            $this->notificationService->send(
                $invitation->invitee,
                'store_invitation',
                __('api.invitation.notification_title', ['store' => $invitation->store->name]),
                __('api.invitation.notification_message', ['store' => $invitation->store->name]),
                'in_app',
                [
                    'invitation_id' => $invitation->id,
                    'store_id' => $invitation->store_id,
                ]
            );

            event(new InvitationSent($invitation));

            return $invitation;
        });
    }

    public function listStoreInvitations(Store $store, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $store->invitations()->with('invitee.profile');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['role'])) {
            $query->where('role', $filters['role']);
        }

        if (!empty($filters['search'])) {
            $query->whereHas('invitee', function ($q) use ($filters) {
                $q->where('email', 'like', '%' . $filters['search'] . '%')
                  ->orWhereHas('profile', function ($q2) use ($filters) {
                      $q2->where('first_name', 'like', '%' . $filters['search'] . '%')
                         ->orWhere('last_name', 'like', '%' . $filters['search'] . '%');
                  });
            });
        }

        return $query->latest()->paginate($perPage);
    }

    public function listUserInvitations(User $user, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $user->receivedInvitations()->with('store');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        } else {
            // Default to pending if not specified, or allow fetching all if explicitly asked? 
            // The previous default was pending. We will keep it but allow overriding.
            if (!isset($filters['status'])) {
                $query->where('status', 'pending');
            }
        }

        if (!empty($filters['role'])) {
            $query->where('role', $filters['role']);
        }

        if (!empty($filters['store_id'])) {
            $query->where('store_id', $filters['store_id']);
        }

        return $query->latest()->paginate($perPage);
    }

    public function expireOverdueInvitations(): int
    {
        $count = StoreInvitation::pending()
            ->where('expires_at', '<', now())
            ->update(['status' => InvitationStatus::EXPIRED]);

        return $count;
    }
}
