<?php

// ============================================================================
// 1. NOTIFICATION MODEL: Domain/Notification/Models/Notification.php
// ============================================================================

namespace App\Domain\Notification\Models;

use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return \Database\Factories\NotificationFactory::new();
    }

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'data',
        'channel',
        'status',
        'reference_type',
        'reference_id',
        'sent_at',
        'read_at',
    ];

    protected $casts = [
        'data' => 'array',
        'sent_at' => 'datetime',
        'read_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    protected $appends = [
        'is_read',
        'is_sent',
        'time_ago',
    ];

    // ========================================================================
    // RELATIONSHIPS
    // ========================================================================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ========================================================================
    // ACCESSORS
    // ========================================================================

    public function getIsReadAttribute(): bool
    {
        return !is_null($this->read_at);
    }

    public function getIsSentAttribute(): bool
    {
        return $this->status === 'sent';
    }

    public function getTimeAgoAttribute(): string
    {
        return $this->created_at->diffForHumans();
    }

    // ========================================================================
    // HELPER METHODS
    // ========================================================================

    public function markAsRead(): bool
    {
        if ($this->is_read) {
            return false;
        }

        return $this->update(['read_at' => now()]);
    }

    public function markAsUnread(): bool
    {
        return $this->update(['read_at' => null]);
    }

    public function markAsSent(): bool
    {
        return $this->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    public function markAsFailed(string $reason = null): bool
    {
        return $this->update([
            'status' => 'failed',
            'data' => array_merge($this->data ?? [], [
                'failure_reason' => $reason,
                'failed_at' => now()->toIso8601String(),
            ]),
        ]);
    }

    // ========================================================================
    // SCOPES
    // ========================================================================

    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    public function scopeRead($query)
    {
        return $query->whereNotNull('read_at');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByChannel($query, string $channel)
    {
        return $query->where('channel', $channel);
    }

    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}