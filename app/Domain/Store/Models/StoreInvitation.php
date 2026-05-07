<?php

namespace App\Domain\Store\Models;

use App\Domain\Store\Enums\InvitationStatus;
use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoreInvitation extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'invited_by_user_id',
        'invitee_user_id',
        'role',
        'permissions',
        'status',
        'message',
        'expires_at',
        'accepted_at',
        'declined_at',
    ];

    protected $casts = [
        'permissions' => 'array',
        'status' => InvitationStatus::class,
        'expires_at' => 'datetime',
        'accepted_at' => 'datetime',
        'declined_at' => 'datetime',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by_user_id');
    }

    public function invitee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invitee_user_id');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', InvitationStatus::PENDING);
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('status', InvitationStatus::EXPIRED)
            ->orWhere('expires_at', '<', now());
    }

    public function scopeValid(Builder $query): Builder
    {
        return $query->pending()->where('expires_at', '>', now());
    }

    public function isExpired(): bool
    {
        return $this->status === InvitationStatus::EXPIRED || $this->expires_at->isPast();
    }

    public function isPending(): bool
    {
        return $this->status === InvitationStatus::PENDING;
    }

    public function markAsAccepted(): void
    {
        $this->update([
            'status' => InvitationStatus::ACCEPTED,
            'accepted_at' => now(),
        ]);
    }

    public function markAsDeclined(): void
    {
        $this->update([
            'status' => InvitationStatus::DECLINED,
            'declined_at' => now(),
        ]);
    }

    public function markAsCancelled(): void
    {
        $this->update([
            'status' => InvitationStatus::CANCELLED,
        ]);
    }

    public function markAsExpired(): void
    {
        $this->update([
            'status' => InvitationStatus::EXPIRED,
        ]);
    }
}
